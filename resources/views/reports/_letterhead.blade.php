{{-- Invoice / report letterhead: company logo with name below it --}}
@php $logoPath = public_path('images/logo.jpeg'); @endphp
<div style="text-align:center; border-bottom:2px solid #1e3a5f; padding-bottom:8px; margin-bottom:10px;">
    @if (file_exists($logoPath))
        <img src="{{ $logoPath }}" alt="logo" style="height:90px; width:auto;"><br>
    @endif
    <div style="font-size:20px; font-weight:bold; color:#1e3a5f; margin-top:4px;">Ali Building Construction Group</div>
</div>
