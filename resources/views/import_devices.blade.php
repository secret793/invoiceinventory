<form action="{{ route('devices.import') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="file" required>
    <button type="submit">Import Devices</button>
</form>
