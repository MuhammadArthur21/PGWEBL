@extends('layouts/template')

@section('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">

    <style>
        #map {
            width: 100%;
            height: calc(100vh - 56px);
        }
    </style>
@endsection

@section('content')
    <div id="map"></div>

    @foreach(['Point', 'Polyline', 'Polygon'] as $type)
        <div class="modal fade" id="Create{{ $type }}Modal" tabindex="-1" aria-labelledby="modalLabel{{ $type }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="modalLabel{{ $type }}">Create {{ $type }}</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route(strtolower($type).'s.store') }}" enctype="multipart/form-data">
                        <div class="modal-body">
                            @csrf
                            <div class="mb-3">
                                <label for="name_{{ strtolower($type) }}" class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" id="name_{{ strtolower($type) }}"
                                    placeholder="Example {{ strtolower($type) }}">
                            </div>
                            <div class="mb-3">
                                <label for="description_{{ strtolower($type) }}" class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="description_{{ strtolower($type) }}" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="geom_{{ strtolower($type) }}" class="form-label">Geometry</label>
                                <textarea class="form-control" id="geom_{{ strtolower($type) }}" name="geom_{{ strtolower($type) }}" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="image_{{ strtolower($type) }}" class="form-label">Photo</label>
                                <input type="file" class="form-control" id="image_{{ strtolower($type) }}" name="image"
                                    accept="image/*"
                                    onchange="previewImage(event, '{{ strtolower($type) }}')">
                                <img src="" alt="Preview" id="preview-image-{{ strtolower($type) }}"
                                    class="img-thumbnail mt-2 d-none" style="max-width: 100%; height: auto;">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@section('scripts')
    <script src="https://unpkg.com/@terraformer/wkt"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        var map = L.map('map').setView([-5.29, 122.861], 13);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        var drawControl = new L.Control.Draw({
            draw: {
                position: 'topleft',
                polyline: true,
                polygon: true,
                rectangle: true,
                circle: false,
                marker: true,
                circlemarker: false
            },
            edit: false
        });

        map.addControl(drawControl);

        map.on('draw:created', function(e) {
            var type = e.layerType,
                layer = e.layer,
                drawnJSONObject = layer.toGeoJSON(),
                objectGeometry = Terraformer.geojsonToWKT(drawnJSONObject.geometry);

            if (['polyline', 'polygon', 'rectangle', 'marker'].includes(type)) {
                const idType = type === 'marker' ? 'point' : type;
                $('#geom_' + idType).val(objectGeometry);
                $('#Create' + idType.charAt(0).toUpperCase() + idType.slice(1) + 'Modal').modal('show');
            }

            drawnItems.addLayer(layer);
        });

        // Image preview function
        function previewImage(event, type) {
            const input = event.target;
            const file = input.files[0];
            const preview = document.getElementById(`preview-image-${type}`);

            if (file && file.type.startsWith('image/')) {
                const url = URL.createObjectURL(file);
                preview.src = url;
                preview.classList.remove('d-none');
            } else {
                preview.src = '';
                preview.classList.add('d-none');
                alert("Please select a valid image file.");
            }
        }

        // Load GeoJSON data
        const layers = {
            points: "{{ route('api.points') }}",
            polylines: "{{ route('api.polylines') }}",
            polygons: "{{ route('api.polygons') }}"
        };

        Object.entries(layers).forEach(([layerName, url]) => {
            const layer = L.geoJson(null, {
                onEachFeature: function (feature, layer) {
                    const popupContent = "Kabupaten/Kota: " + feature.properties.kab_kota + "<br>" +
                        "Provinsi: " + feature.properties.provinsi + "<br>" +
                        "<img src='{{  asset('storage/images') }}/" + feature.properties.image + "' width='200' alt=''>"
                    layer.on({
                        click: () => layer.bindPopup(popupContent).openPopup(),
                        mouseover: () => layer.bindTooltip(feature.properties.kab_kota).openTooltip()
                    });
                },
            });
            $.getJSON(url, function (data) {
                layer.addData(data);
                map.addLayer(layer);
            });
        });
    </script>
@endsection
