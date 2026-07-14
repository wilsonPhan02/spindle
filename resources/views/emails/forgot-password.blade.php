<div style="background-color: #f8fafc; padding: 40px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 570px; margin: 0 auto;">
        
        {{-- Main Card --}}
        <div style="background-color: #ffffff; padding: 45px; border-radius: 2px; text-align: left; border: 1px solid #e8e5ef; box-shadow: 0 2px 0 rgba(0, 0, 150, 0.025), 2px 4px 0 rgba(0, 0, 150, 0.015);">
            
            {{-- Header --}}
            <h1 style="color: #37322E; font-size: 18px; font-weight: bold; margin-top: 0;">{{ __('Hello!') }}</h1>
            
            {{-- Body --}}
            <p style="color: #718096; font-size: 16px; line-height: 1.5; margin-bottom: 25px;">
                You are receiving this email because we received a password reset request for your account.
            </p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $url }}" style="background-color: #37322E; color: #ffffff; padding: 10px 18px; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold; font-size: 15px;">
                    Reset Password
                </a>
            </div>
            
            {{-- Notes --}}
            <p style="color: #718096; font-size: 16px; line-height: 1.5;">
                This password reset link will expire in 60 minutes.
            </p>
            <p style="color: #718096; font-size: 16px; line-height: 1.5; margin-bottom: 35px;">
                If you did not request a password reset, no further action is required.
            </p>

            {{-- Regards --}}
            <p style="color: #718096; font-size: 16px; line-height: 1.5; margin: 0;">
                Regards,<br>
                <span style="color: #3d4852; font-weight: bold;">Spindle</span>
            </p>

            {{-- Divider --}}
            <hr style="border: none; border-top: 1px solid #e8e5ef; margin: 30px 0;">

            <p style="font-size: 12px; color: #718096; line-height: 1.5;">
                If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
                <br>
                <a href="{{ $url }}" style="color: #3869d4; word-break: break-all; text-decoration: underline;">{{ $url }}</a>
            </p>
        </div>        
    </div>
</div>