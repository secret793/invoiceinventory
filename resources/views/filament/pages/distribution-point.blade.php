@extends('layouts.app')

@section('content')
    <h1>{{ $distributionPoint->name }}</h1>
    <p>Location: {{ $distributionPoint->location }}</p>
    {{-- Add more details as needed --}}
@endsection
