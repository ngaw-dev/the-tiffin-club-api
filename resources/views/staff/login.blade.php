<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff login</title>
    <style>
        body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        form { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); display: grid; gap: 1rem; width: 20rem; }
        input { padding: .5rem; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: .5rem; border: 0; border-radius: 4px; background: #16a34a; color: #fff; cursor: pointer; }
        ul { color: #b91c1c; margin: 0; padding-left: 1rem; }
    </style>
</head>
<body>
    <form method="POST" action="/staff/login">
        @csrf
        <h1>Staff login</h1>
        @if ($errors->any())
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
        <button type="submit">Log in</button>
    </form>
</body>
</html>
