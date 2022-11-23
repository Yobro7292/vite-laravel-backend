@component('mail::message')
    <h1>{{ $mailData['title'] }}</h1>
    <br>
    To reset your password, click on the button or click on the link below.
    @component('mail::button', ['url' => $mailData['url']])
        Reset Password
    @endcomponent
    <p>
        <a href={{ $mailData['url'] }}> {{ $mailData['url'] }} </a>
    </p>
    <br>
    <h5>Please note that this url is valid only for five minutes.</h5>
    <br>
    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
