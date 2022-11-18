@component('mail::message')
    <h1>{{ $mailData['title'] }}</h1>
    <br>
    To reset your password, click on the button or click on the link below.
    @component('mail::button', ['url' => $mailData['url']])
        Reset Password
    @endcomponent
    <a href={{ $mailData['url'] }}> {{ $mailData['url'] }} </a>
    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
