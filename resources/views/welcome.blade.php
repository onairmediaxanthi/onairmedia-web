<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>On Air Media Group</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; text-align: center;
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
               background: radial-gradient(circle at 50% 30%, #3a0d1f 0, #0b0b10 65%); color: #f4f4f6; }
        h1 { font-size: clamp(28px, 6vw, 56px); margin: 0 0 12px; letter-spacing: -0.02em; }
        p { color: #a8a8b5; margin: 0; }
        .bars { display: flex; gap: 6px; justify-content: center; height: 40px; align-items: end; margin-bottom: 28px; }
        .bars span { width: 6px; background: #e11d48; border-radius: 3px; animation: eq 1.1s ease-in-out infinite; }
        .bars span:nth-child(2) { animation-delay: .15s } .bars span:nth-child(3) { animation-delay: .3s }
        .bars span:nth-child(4) { animation-delay: .45s } .bars span:nth-child(5) { animation-delay: .6s }
        @keyframes eq { 0%,100% { height: 20% } 50% { height: 100% } }
        @media (prefers-reduced-motion: reduce) { .bars span { animation: none; height: 60%; } }
    </style>
</head>
<body>
    <main>
        <div class="bars" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
        <h1>Ακούγεσαι. Φαίνεσαι. Μετράς.</h1>
        <p>Το νέο On Air Media Group ετοιμάζεται.</p>
    </main>
</body>
</html>
