{{-- Collpasible content for documents, displaying file date, type, and description --}}
<div class="p-4 bg-gray-100 rounded-lg">
    <div>
        <span class="font-medium">
            File Date:
        </span>

        <span>
            {{ $getRecord()->file_date }}
        </span>
    </div>
    <div>
        <span class="font-medium">
            File Type:
        </span>

        <span>
            {{ ucfirst($getRecord()->file_type) }}
        </span>
    </div>

    <div>
        <span class="font-medium">
            Description:
        </span>

        <span>
            {{ $getRecord()->description ?? 'No description' }}
        </span>
    </div>
</div>
