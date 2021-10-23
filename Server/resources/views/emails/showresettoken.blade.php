<style>
    p, h1, h2, h3 {
        direction: rtl !important;
        text-align: right !important;
    }
</style>

@component('mail::message')
# وب سایت لویالیت

کد تاییدیه بازیابی گذرواژه شما به شرح زیر می‌باشد:
{{ $token }}
کد بالا فقط تا زمانی که درحال بازیابی گذرواژه خود هستید، معتبر خواهد بود.

ایام به کام،<br>
{{ config('app.name') }}
@endcomponent
