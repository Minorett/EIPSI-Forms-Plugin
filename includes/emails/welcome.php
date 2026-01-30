<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a {{survey_name}}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f9f9f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px 20px; border-radius: 8px; margin-top: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #eeeeee; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #1a1a1a; font-size: 24px; font-weight: 600; }
        .content { margin-bottom: 30px; font-size: 16px; }
        .button { display: inline-block; background-color: #0073aa; color: #ffffff !important; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: 500; margin-top: 20px; text-align: center; font-size: 16px; }
        .button:hover { background-color: #005177; }
        .footer { font-size: 13px; color: #666666; border-top: 1px solid #eeeeee; padding-top: 20px; text-align: center; }
        .footer p { margin: 5px 0; }
        @media only screen and (max-width: 600px) {
            .container { padding: 20px 15px; margin: 0; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenido a {{survey_name}}</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{first_name}}</strong>,</p>
            <p>Gracias por participar en nuestro estudio. Tu colaboración es fundamental para ayudarnos a entender mejor los procesos terapéuticos.</p>
            <p><strong>Tu estudio comienza hoy.</strong></p>
            <p>Hemos preparado todo para tu primera evaluación. Puedes acceder directamente cuando estés listo:</p>
            <p style="text-align: center;">
                <a href="{{magic_link}}" class="button">Comenzar Estudio</a>
            </p>
            <p>Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
            <span style="font-size: 13px; color: #555; word-break: break-all;">{{magic_link}}</span></p>
        </div>
        <div class="footer">
            <p>Investigador Principal: {{investigator_name}}</p>
            <p>Si tienes dudas, contáctanos en: <a href="mailto:{{investigator_email}}" style="color: #666;">{{investigator_email}}</a></p>
        </div>
    </div>
</body>
</html>