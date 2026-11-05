@if (session('estado'))
    <div class="alerta info">{{ session('estado') }}</div>
@endif

@if ($errors->any())
    <div class="alerta error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif