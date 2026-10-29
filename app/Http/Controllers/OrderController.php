<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CustomerEvents;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Οι παραγγελίες του πελάτη στο Studio του: πορεία, πληρωμή, τιμολόγιο */
class OrderController extends Controller
{
    protected function own(Request $request, Order $order): Order
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return $order;
    }

    public function index(Request $request): View
    {
        return view('orders.index', [
            'orders' => $request->user()->orders()->with('quote')->latest()->paginate(20),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->own($request, $order);

        return view('orders.show', [
            'order' => $order->load('quote'),
            'events' => $order->events()->where('internal', false)->get(),
            'files' => $order->files()->get(),
            'accounts' => \App\Support\BankAccounts::all(),
        ]);
    }

    public function deposit(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $this->own($request, $order);
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'receipt' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp'],
        ], [
            'receipt.max' => 'Το αρχείο είναι μεγαλύτερο από 5 MB.',
            'receipt.mimes' => 'Ανεβάστε PDF ή φωτογραφία (JPG, PNG).',
        ]);

        try {
            $service->declareDeposit($order, $data['note'] ?? null, $request->file('receipt'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Ευχαριστούμε! Ελέγχουμε την κατάθεση και σας ενημερώνουμε με email μόλις επιβεβαιωθεί.');
    }

    public function upload(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $this->own($request, $order);
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'max:'.(Order::FILE_MAX_MB * 1024), 'extensions:'.implode(',', Order::FILE_TYPES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'files.required' => 'Διαλέξτε τουλάχιστον ένα αρχείο.',
            'files.max' => 'Έως 10 αρχεία κάθε φορά.',
            'files.*.max' => 'Κάποιο αρχείο είναι μεγαλύτερο από '.Order::FILE_MAX_MB.' MB. Για μεγαλύτερα, στείλτε μας σύνδεσμο (WeTransfer, Google Drive) στη σημείωση.',
            'files.*.extensions' => 'Δεν δεχόμαστε αυτό το είδος αρχείου. Ήχος, εικόνες, βίντεο, PDF, Word ή ZIP.',
            'files.*.uploaded' => 'Το αρχείο δεν ανέβηκε (πιθανόν πολύ μεγάλο). Για μεγάλα αρχεία στείλτε μας σύνδεσμο στη σημείωση.',
        ]);

        try {
            $n = $service->addFiles($order, $request->file('files', []), $request->user(), 'customer', $request->input('note'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $n === 1 ? 'Το αρχείο ανέβηκε.' : 'Ανέβηκαν '.$n.' αρχεία.');
    }

    public function materialsDone(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $this->own($request, $order);
        $note = $request->validate(['note' => ['nullable', 'string', 'max:1000']])['note'] ?? null;
        try {
            $service->materialsDone($order, $note);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Ευχαριστούμε! Ξεκινάμε την προετοιμασία και σας ενημερώνουμε για το πρόγραμμα.');
    }

    public function file(Request $request, Order $order, \App\Models\OrderFile $file): StreamedResponse
    {
        $this->own($request, $order);
        abort_unless($file->order_id === $order->id && Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->name, ['Cache-Control' => 'private, no-store']);
    }

    public function invoice(Request $request, Order $order): StreamedResponse
    {
        $this->own($request, $order);
        abort_unless($order->invoice_path && Storage::disk('local')->exists($order->invoice_path), 404);
        CustomerEvents::record('invoice_download', $request->user(), $order->quote, ['order' => $order->number]);

        return Storage::disk('local')->download($order->invoice_path, 'timologio-'.preg_replace('/[^A-Za-z0-9-]/', '', (string) $order->invoice_number ?: $order->id).'.pdf', [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
