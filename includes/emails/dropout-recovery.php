<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Te extrañamos - {{survey_name}}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f9f9f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px 20px; border-radius: 8px; margin-top: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #eeeeee; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #1a1a1a; font-size: 24px; font-weight: 600; }
        .content { margin-bottom: 30px; font-size: 16px; }
        .button { display: inline-block; background-color: #0073aa; color: #ffffff !important; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: 500; margin-top: 20px; text-align: center; font-size: 16px; }
        .button:hover { background-color: #005177; }
        .message-box { background-color: #fff8e1; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 4px; margin-top: 20px; }
        .footer { font-size: 13px; color: #666666; border-top: 1px solid #eeeeee; padding-top: 20px; text-align: center; }
        .footer p { margin: 5px 0; }
        @media only screen and (max-width: 600px) { .container { padding: 20px 15px; margin: 0; border-radius: 0; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Te extrañamos</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{first_name}}</strong>,</p>
            <p>Notamos que no pudiste completar la evaluación <strong>{{wave_index}}</strong> del estudio <strong>{{survey_name}}</strong>.</p>
            
            <p>Entendemos que a veces surgen imprevistos o falta de tiempo. Sin embargo, tu respuesta es muy importante para la integridad del estudio.</p>
            
            <p style="text-align: center;">
                <a href="{{magic_link}}" class="button">Retomar Estudio Ahora</a>
            </p>
            
            <div class="message-box">
                <p style="margin: 0;"><strong>¿Necesitas ayuda o tuviste algún problema técnico?</strong></p>
                <p style="margin: 5px 0 0;">Por favor contáctanos directamente a <a href="mailto:{{investigator_email}}" style="color: #856404; text-decoration: underline;">{{investigator_email}}</a> y te ayudaremos.</p>
            </div>
            
            <p style="margin-top: 20px;">Aún estás a tiempo de ponerte al día.</p>
        </div>
        <div class="footer">
            <p>Investigador Principal: {{investigator_name}}</p>
            <p>Gracias por tu esfuerzo continuo.</p>
        </div>
    </div>
</body>
</html>