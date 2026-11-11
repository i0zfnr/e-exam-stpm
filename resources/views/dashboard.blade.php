@extends('layouts.app')
@section('title', 'Papan pemuka · e-Exam STPM')
@section('content')
<div class="card">
    <div class="row"><div><div class="pill">PAPAN PEMUKA</div><h1 style="margin:18px 0 5px">Selamat datang, {{ $name }}</h1><p class="muted" style="margin-top:0">Anda telah log masuk ke Sistem Peperiksaan STPM.</p></div><span class="pill">{{ ucfirst(str_replace('_', ' ', $user->role ?? 'pengguna')) }}</span></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-top:28px">
        <div class="card"><strong>Akaun</strong><p class="muted">{{ $user->email }}</p></div>
        <div class="card"><strong>Peranan</strong><p class="muted">{{ ucfirst(str_replace('_', ' ', $user->role ?? 'pengguna')) }}</p></div>
        <div class="card"><strong>Sistem</strong><p class="muted">Pengurusan peperiksaan STPM</p></div>
    </div>
    <p class="muted" style="margin-top:24px">Modul pengurusan dan paparan peperiksaan sedang dipindahkan ke Laravel.</p>
</div>
@endsection
