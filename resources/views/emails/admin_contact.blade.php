@component('mail::message')
    # New Contact Message

    **Name:** {{ $data['name'] }}
    **Email:** {{ $data['email'] }}
    **Subject:** {{ $data['subject'] }}

    ---

    ### Message:
    {{ $data['message'] }}

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
