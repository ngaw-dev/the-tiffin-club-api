{{-- resources/views/staff/layout.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Staff')</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; display: flex; min-height: 100vh; background: #f5f5f5; color: #111; }
        aside { width: 200px; background: #111827; color: #fff; padding: 1.25rem 1rem; display: flex; flex-direction: column; gap: .25rem; }
        aside .brand { font-weight: 700; margin-bottom: 1rem; }
        aside a { display: block; padding: .5rem .75rem; border-radius: 6px; color: #d1d5db; text-decoration: none; }
        aside a:hover { background: #1f2937; }
        aside a.active { background: #16a34a; color: #fff; }
        aside form { margin-top: auto; }
        aside button { font: inherit; width: 100%; padding: .5rem .75rem; border: 0; border-radius: 6px; background: #374151; color: #d1d5db; cursor: pointer; }
        main { flex: 1; padding: 1.5rem; }
        table { border-collapse: collapse; width: 100%; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        th, td { text-align: left; padding: .6rem .8rem; border-bottom: 1px solid #eee; font-size: .9rem; }
        form.inline { display: inline-flex; gap: .4rem; align-items: center; margin: 0; }
        select, input, button { font: inherit; padding: .3rem .4rem; }
        main > form button, .btn { border: 0; border-radius: 4px; background: #16a34a; color: #fff; cursor: pointer; }
        .paid-full { color: #16a34a; font-weight: 600; }
        .paid-partial { color: #b45309; }
        .badge-owes, .badge-credit { display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-weight: 600; }
        .badge-owes { background: #fee2e2; color: #b91c1c; }
        .badge-credit { background: #dcfce7; color: #15803d; }
        nav.page { display: flex; gap: 1rem; margin-bottom: 1rem; }
        nav.page a { padding: .4rem .8rem; border-radius: 4px; background: #e5e7eb; color: #111; text-decoration: none; }
        nav.page a.active { background: #16a34a; color: #fff; }
        header.page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        @media (max-width: 640px) { body { flex-direction: column; } aside { width: auto; flex-direction: row; align-items: center; } aside a { padding: .4rem .6rem; } aside form { margin: 0 0 0 auto; } }
    </style>
</head>
<body>
<aside>
    <div class="brand">The Tiffin Club</div>
    <a href="{{ route('staff.orders') }}" @class(['active' => request()->routeIs('staff.orders')])>Orders</a>
    <a href="{{ route('staff.payments') }}" @class(['active' => request()->routeIs('staff.payments')])>Payments</a>
    <form method="POST" action="{{ route('staff.logout') }}">
        @csrf
        <button type="submit">Log out</button>
    </form>
</aside>
<main>
    @yield('content')
</main>
</body>
</html>
