{{-- Collpasible content for documents, displaying file date, type, and description --}}
<div class="p-4 bg-gray-100 rounded-lg">
    <div>
        <span class="font-medium">
            Uploaded by:
        </span>

        <span>
        {{ $getRecord()->createdBy ? $getRecord()->createdBy->name . ' ' . $getRecord()->createdBy->lastname : 'Deleted User' }}
        </span>
    </div>

    <div>
        <span class="font-medium">
            File date:
        </span>

        <span>
            {{ $getRecord()->file_date }}
        </span>
    </div>

    <div>
        <span class="font-medium">
            Description:
        </span>

        <span  class="block break-words w-full">
            {{ $getRecord()->description ?? 'No description' }}
        </span>
    </div>
</div>
