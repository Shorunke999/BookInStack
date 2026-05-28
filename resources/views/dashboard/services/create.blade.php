@extends('layouts.app')

@section('title', 'New Service')
@section('page-title', 'New Service')

@section('content')

<div style="max-width:640px;">

    <form method="POST" action="{{ route('services.store') }}">
        @csrf

        @include('dashboard.services._form', ['service' => null])

        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-primary">Create Service</button>
            <a href="{{ route('services.index') }}" class="btn btn-white">Cancel</a>
        </div>
    </form>

</div>

@endsection