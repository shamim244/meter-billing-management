{{-- Hidden Delete Forms --}}
@foreach($tags as $tag)
    @if(empty($tag['is_default']) && $defaultTag !== $tag['code'])
        <form id="delete-tag-{{ $tag['code'] }}" method="POST" action="{{ route('admin.tags.destroy', $tag['code']) }}" onsubmit="return confirm('Are you sure you want to permanently delete tag \'{{ $tag['label'] }}\' ({{ $tag['code'] }})?');" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endforeach
