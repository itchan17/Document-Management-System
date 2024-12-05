<tr>
<td class="header">
@if (trim($slot) === 'Laravel')
<img src="{{ asset('/images/engineering_logo.svg') }}" alt="Office Of The City Engineer" style="max-width: 100px; height: auto;">

@else
{{ $slot }}
@endif
</a>
</td>
</tr>
