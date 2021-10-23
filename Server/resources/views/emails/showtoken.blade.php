<style>
    p, h1, h2, h3 {
        direction: rtl !important;
        text-align: right !important;
    }
</style>

@component('mail::message')
# وب سایت تکواش

کد تاییدیه ایمیل شما به شرح زیر می‌باشد:
{{ $token }}

@component('mail::button', ['url' => env('USER_DASHBOARD_URL')])
بازگشت به پنل کاربری
@endcomponent

ایام به کام،<br>
{{ config('app.name') }}
@endcomponent
