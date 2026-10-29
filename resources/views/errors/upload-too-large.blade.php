<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Πολύ μεγάλα αρχεία — On Air Media Group</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f6f7fb; color: #121a33; font: 17px/1.55 system-ui, -apple-system, "Segoe UI", sans-serif; padding: 16px; }
        @media (prefers-color-scheme: dark) { body { background: #0b1022; color: #eef1f8; } .card { background: #121a33 !important; } p { color: #b9c0d4 !important; } }
        .card { max-width: 30rem; background: #fff; border-radius: 24px; padding: 32px; box-shadow: 0 20px 50px -20px rgba(18, 26, 51, .25); }
        h1 { margin: 0 0 .5rem; font-size: 1.6rem; }
        p { margin: 0 0 1rem; color: #4a5270; }
        a { display: inline-block; margin-top: .5rem; background: #c80c92; color: #fff; text-decoration: none; font-weight: 600; padding: .8rem 1.5rem; border-radius: 999px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Πολύ μεγάλα αρχεία για μία αποστολή</h1>
        <p>Ανεβάστε τα σε μικρότερες ομάδες, έως {{ \App\Models\Order::BATCH_MAX_MB }} MB συνολικά κάθε φορά και {{ \App\Models\Order::FILE_MAX_MB }} MB το καθένα.</p>
        <p>Για πολύ μεγάλα αρχεία (π.χ. βίντεο), γράψτε μας στη σημείωση έναν σύνδεσμο WeTransfer ή Google Drive.</p>
        <a href="{{ $back }}">← Πίσω στην παραγγελία</a>
    </div>
</body>
</html>
