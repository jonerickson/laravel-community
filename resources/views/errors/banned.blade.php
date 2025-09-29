<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Access Denied</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: white;
                border-radius: 12px;
                padding: 3rem 2rem;
                text-align: center;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                max-width: 500px;
                margin: 2rem;
            }
            .icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }
            h1 {
                color: #e53e3e;
                margin-bottom: 1rem;
                font-size: 2rem;
            }
            p {
                color: #666;
                line-height: 1.6;
                margin-bottom: 1.5rem;
            }
            .support {
                background: #f7fafc;
                border-radius: 8px;
                padding: 1rem;
                color: #4a5568;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">🚫</div>
            <h1>Access Denied</h1>
            <p>{{ $message ?? 'You have been banned from accessing this site.' }}</p>
            <div class="support">If you believe this is an error, please contact support for assistance.</div>
        </div>
    </body>
</html>
