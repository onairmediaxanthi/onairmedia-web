<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\Coupons;
use App\Services\CustomerEvents;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuotePdf;
use App\Services\Quotes\QuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;

/** Οι προσφορές του πελάτη στο Studio του */
class QuoteController extends Controller
{
    public function __construct(protected QuoteService $service) {}

    /** Μόνο ο ιδιοκτήτης βλέπει τη δική του προσφορά· για όλους τους άλλους δεν υπάρχει */
    protected function own(Request $request, Quote $quote): Quote
    {
        abort_unless($quote->user_id === $request->user()->id, 404);

        return $quote;
    }

    public function index(Request $request): View
    {
        return view('quotes.index', [
            'quotes' => $request->user()->quotes()->latest()->paginate(20),
        ]);
    }

    public function show(Request $request, Quote $quote): View
    {
        return view('quotes.show', ['quote' => $this->own($request, $quote)]);
    }

    public function pdf(Request $request, Quote $quote): Response
    {
        $this->own($request, $quote);
        CustomerEvents::record('quote_pdf', $request->user(), $quote);

        return response(QuotePdf::render($quote), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.QuotePdf::filename($quote).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function renew(Request $request, Quote $quote): RedirectResponse
    {
        $this->own($request, $quote);
        abort_unless(in_array($quote->status, ['saved', 'expired'], true), 403);

        try {
            $this->service->renew($quote);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('quotes.show', $quote)->with('status', 'Η προσφορά ανανεώθηκε με τις τρέχουσες τιμές.');
    }

    public function checkout(Request $request, Quote $quote): View|RedirectResponse
    {
        $this->own($request, $quote);
        if (! $quote->isEditable()) {
            return redirect()->route('quotes.show', $quote);
        }

        return view('quotes.checkout', ['quote' => $quote, 'methods' => \App\Services\Orders\OrderService::methodsFor($request->user())]);
    }

    public function submit(Request $request, Quote $quote): RedirectResponse
    {
        $this->own($request, $quote);
        if (! $quote->isEditable()) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Η προσφορά δεν μπορεί πλέον να σταλεί. Ανανεώστε την πρώτα.');
        }
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'accept' => ['accepted'],
            'payment_method' => ['nullable', \Illuminate\Validation\Rule::in(\App\Services\Orders\OrderService::methodsFor($request->user()))],
        ], ['accept.accepted' => 'Χρειάζεται να αποδεχτείτε τους όρους.', 'payment_method.*' => 'Διαλέξτε τρόπο πληρωμής.']);

        try {
            $this->service->submit($quote, $data['notes'] ?? null, $data['payment_method'] ?? 'bank');
        } catch (InvalidArgumentException $e) {
            app(Coupons::class)->remove($quote);

            return redirect()->route('quotes.checkout', $quote)->with('error', 'Το κουπόνι αφαιρέθηκε: '.$e->getMessage());
        }

        return redirect()->route('orders.show', $quote->order)
            ->with('status', 'Λάβαμε την παραγγελία σας. Θα σας επιβεβαιώσουμε εντός μίας εργάσιμης ημέρας και θα βλέπετε εδώ κάθε βήμα.');
    }

    public function applyCoupon(Request $request, Quote $quote): RedirectResponse
    {
        $this->own($request, $quote);
        abort_unless($quote->isEditable(), 403);
        $data = $request->validate(['code' => ['required', 'string', 'max:40']], ['code.required' => 'Γράψτε τον κωδικό του κουπονιού.']);

        try {
            $coupon = app(Coupons::class)->apply($quote, $data['code'], $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Το κουπόνι '.$coupon->code.' εφαρμόστηκε: '.QuoteCalculator::money(-collect($quote->items)->firstWhere('type', 'coupon')['amount']).' έκπτωση.');
    }

    public function removeCoupon(Request $request, Quote $quote): RedirectResponse
    {
        $this->own($request, $quote);
        abort_unless($quote->isEditable(), 403);
        app(Coupons::class)->remove($quote);

        return back()->with('status', 'Το κουπόνι αφαιρέθηκε.');
    }

    public function destroy(Request $request, Quote $quote): RedirectResponse
    {
        $this->own($request, $quote);
        abort_if($quote->status === 'ordered', 403);
        $this->service->delete($quote);

        return redirect()->route('quotes.index')->with('status', 'Η προσφορά '.$quote->number.' διαγράφηκε.');
    }

    public function remindersOff(Request $request, Quote $quote): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            if (! $quote->reminders_off) {
                $quote->forceFill(['reminders_off' => true])->save();
                CustomerEvents::record('reminders_off', $quote->user, $quote);
            }

            return back()->with('status', 'Δεν θα λαμβάνετε άλλες υπενθυμίσεις για αυτή την προσφορά.');
        }

        return view('quotes.reminders-off', ['quote' => $quote]);
    }
}
