@extends('layouts.app')
@section('title', 'Live Scan Map')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<style>#scanMap{height:560px;border-radius:14px;z-index:1}</style>
@endpush

@section('content')
<div class="page-header">
  <div>
    <h1>Live Scan Map</h1>
    <div class="page-breadcrumb"><a href="{{ route('anticounterfeit.dashboard') }}">Anti-Counterfeit</a> / Live Scan Map</div>
  </div>
  <div class="d-flex gap-3 align-items-center small">
    <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#198754"></span>Genuine</span>
    <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#ffc107"></span>Suspicious</span>
    <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#dc3545"></span>Invalid/Recalled</span>
  </div>
</div>

@if(count($points))
  <div class="card"><div class="card-body"><div id="scanMap"></div></div></div>
@else
  <div class="card"><div class="card-body text-center text-muted py-5">
    <i class="bi bi-geo-alt" style="font-size:2rem"></i>
    <p class="mt-2 mb-0">No geo-located verifications yet. Scans from real (non-local) IP addresses will appear here.</p>
  </div></div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
  const points = @json($points);
  if (points.length && window.L) {
    const map = L.map('scanMap').setView([20, 10], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap', maxZoom: 18,
    }).addTo(map);

    const color = r => r === 'genuine' ? '#198754' : (r === 'suspicious' ? '#ffc107' : '#dc3545');
    points.forEach(p => {
      L.circleMarker([p.lat, p.lng], { radius: 6, color: color(p.result), fillColor: color(p.result), fillOpacity: 0.7, weight: 1 })
        .addTo(map).bindPopup(`<b>${p.result.toUpperCase()}</b><br>${p.label}`);
    });

    if (L.heatLayer) {
      L.heatLayer(points.map(p => [p.lat, p.lng, 0.6]), { radius: 25, blur: 18, maxZoom: 6 }).addTo(map);
    }
  }
</script>
@endpush
