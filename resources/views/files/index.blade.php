@extends('layouts.app')

@section('title', 'My Files')

@section('content')
<div class="page-header">
    <h1>My Files</h1>
</div>

<div class="flex-column">
    <!-- Upload Section -->
    <div class="card">
        <h2>Upload New File</h2>
        <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" class="upload-form">
            @csrf
            <div class="form-group">
                <input type="file" name="file" required>
            </div>
            <button type="submit" class="btn btn-primary w-auto mt-4">
                Upload File
            </button>
        </form>
        @if(session('success'))
            <p class="success mt-4">{{ session('success') }}</p>
        @endif
        @if(isset($errors) && $errors->any())
            <p class="error mt-4">{{ $errors->first() }}</p>
        @endif
    </div>

    <!-- My Files -->
    <div class="card">
        <div class="flex-between-center mb-4">
            <h2 class="m-0">My Files</h2>
        </div>
        <p class="text-small">Files you have uploaded.</p>
        <div class="action-list mt-4">
            @forelse($myFiles as $file)
                <div class="action-item flex-between-center">
                    <div class="flex-center flex-1">
                        <div class="action-icon">📄</div>
                        <div>
                            <strong>{{ $file->original_name }}</strong>
                            <p class="text-small">{{ number_format($file->size / 1024, 2) }} KB | {{ $file->created_at->diffForHumans() }}</p>
                            
                            @php
                                $sharedWith = $file->users->where('id', '!=', Auth::id());
                            @endphp
                            @if($sharedWith->count() > 0)
                                <div class="mt-2">
                                    <p class="text-small" style="color: #0066cc;">Shared with (click to edit): 
                                        @foreach($sharedWith as $sharedUser)
                                            <span class="status-badge status-loading" style="margin: 0 2px; padding: 2px 6px; font-size: 10px; cursor: pointer;" 
                                                  onclick="openShareModal({{ $file->id }}, '{{ $file->original_name }}', {{ $sharedUser->id }}, '{{ $sharedUser->pivot->permission }}')">
                                                {{ $sharedUser->name }} ({{ $sharedUser->pivot->permission }})
                                            </span>
                                        @endforeach
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="user-nav flex-gap-2">
                        <button type="button" class="btn btn-primary btn-small" onclick="openShareModal({{ $file->id }}, '{{ $file->original_name }}')">
                            Add User
                        </button>
                        <a href="{{ route('files.show', $file) }}" class="btn btn-primary btn-small">
                            Download
                        </a>
                        <form action="{{ route('files.destroy', $file) }}" method="POST" onsubmit="return confirm('Are you sure?')" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-small">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-muted">No files found.</p>
            @endforelse
        </div>
    </div>

    <!-- Shared Files -->
    <div class="card">
        <div class="flex-between-center mb-4">
            <h2 class="m-0">Shared Files</h2>
        </div>
        <p class="text-small">Files shared with you by others.</p>
        <div class="action-list mt-4">
            @forelse($sharedFiles as $file)
                <div class="action-item flex-between-center">
                    <div class="flex-center flex-1">
                        <div class="action-icon">👁️</div>
                        <div>
                            <strong>{{ $file->original_name }}</strong>
                            <p class="text-small">{{ number_format($file->size / 1024, 2) }} KB | {{ $file->pivot->permission }}</p>
                        </div>
                    </div>
                    <a href="{{ route('files.show', $file) }}" class="btn btn-primary btn-small">
                        Download
                    </a>
                </div>
            @empty
                <p class="text-muted">No shared files found.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="shareModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="m-0" id="modalTitle">Share File</h2>
            <button type="button" class="modal-close" onclick="closeShareModal()">&times;</button>
        </div>
        <p id="shareFileName" class="text-small mb-4"></p>
        <form id="shareForm" method="POST">
            @csrf
            <div class="form-group">
                <label>Select User</label>
                <select name="user_id" id="userSelect" required class="form-group" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Choose a user...</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mt-4">
                <label>Permission</label>
                <select name="permission" id="permissionSelect" required class="form-group" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="viewer">Viewer</option>
                    <option value="editor">Editor</option>
                    <option value="none">None (Remove access)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mt-4" id="submitBtn">Share File</button>
        </form>
    </div>
</div>

<script>
    function openShareModal(fileId, fileName, userId = null, permission = 'viewer') {
        const modal = document.getElementById('shareModal');
        const form = document.getElementById('shareForm');
        const fileNameEl = document.getElementById('shareFileName');
        const userSelect = document.getElementById('userSelect');
        const permissionSelect = document.getElementById('permissionSelect');
        const modalTitle = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitBtn');
        
        form.action = `/files/${fileId}/share`;
        fileNameEl.textContent = `File: ${fileName}`;
        
        if (userId) {
            modalTitle.textContent = 'Edit Permission';
            submitBtn.textContent = 'Update Permission';
            userSelect.value = userId;
            // Disable select if editing to prevent changing the wrong user, 
            // but we need the value in the form POST, so we'll handle that
            userSelect.style.backgroundColor = '#f1f3f5';
            permissionSelect.value = permission;
        } else {
            modalTitle.textContent = 'Share File';
            submitBtn.textContent = 'Share File';
            userSelect.value = '';
            userSelect.style.backgroundColor = '#fff';
            permissionSelect.value = 'viewer';
        }

        modal.classList.add('show');
    }

    function closeShareModal() {
        document.getElementById('shareModal').classList.remove('show');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('shareModal');
        if (event.target == modal) {
            closeShareModal();
        }
    }
</script>
@endsection
