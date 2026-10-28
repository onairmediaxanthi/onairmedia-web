<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Προεπισκόπηση — On Air Media Group</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px;
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
               background: radial-gradient(circle at 30% 20%, #3a0d2e 0, #0b0b10 60%); color: #f4f4f6; }
        form { width: 100%; max-width: 360px; background: #15151c; border: 1px solid #2a2a35;
               border-radius: 16px; padding: 28px; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        p { margin: 0 0 20px; color: #a8a8b5; font-size: 14px; }
        label { display: block; font-size: 13px; margin-bottom: 6px; }
        input { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #3a3a48;
                background: #0f0f15; color: inherit; font-size: 16px; }
        input:focus { outline: 2px solid #c80c92; outline-offset: 1px; }
        button { margin-top: 16px; width: 100%; padding: 12px; border: 0; border-radius: 10px;
                 background: #c80c92; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; }
        .error { color: #f9a8d4; font-size: 13px; margin-top: 8px; }
    </style>
</head>
<body>
    <form method="post" action="{{ route('preview.unlock') }}">
        @csrf
        <h1>On Air Media Group</h1>
        <p>Το νέο site βρίσκεται υπό κατασκευή. Η πρόσβαση γίνεται με κωδικό.</p>
        <label for="password">Κωδικός προεπισκόπησης</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required autofocus>
        @error('password')<div class="error" role="alert">{{ $message }}</div>@enderror
        <button type="submit">Είσοδος</button>
    </form>
</body>
</html>
