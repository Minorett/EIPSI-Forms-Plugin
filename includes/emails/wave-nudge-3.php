<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urgente: Evaluación pendiente - {{survey_name}}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f9f9f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px 20px; border-radius: 8px; margin-top: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #eeeeee; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #d32f2f; font-size: 24px; font-weight: 600; }
        .content { margin-bottom: 30px; font-size: 16px; }
        .highlight-box { background-color: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 20px 0; border-radius: 0 4px 4px 0; }
        .due-box { background-color: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 20px 0; border-radius: 0 4px 4px 0; }
        .urgent-text { color: #d32f2f; font-weight: 500; }
        .button { display: inline-block; background-color: #f44336; color: #ffffff !important; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: 600; margin-top: 10px; text-align: center; font-size: 16px; width: 100%; box-sizing: border-box; }
        .button:hover { background-color: #d32f2f; }
        .meta-info { font-size: 14px; color: #555; margin-top: 10px; text-align: center; }
        .footer { font-size: 13px; color: #666666; border-top: 1px solid #eeeeee; padding-top: 20px; text-align: center; }
        .footer p { margin: 5px 0; }
        @media only screen and (min-width: 400px) { .button { width: auto; } }
        @media only screen and (max-width: 600px) { .container { padding: 20px 15px; margin: 0; border-radius: 0; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Urgente: Evaluación pendiente</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{first_name}}</strong>,</p>
            <p class="urgent-text">Tu evaluación lleva mucho tiempo pendiente y estamos en riesgo de perder tus datos valiosos.</p>
            
            <div class="highlight-box">
                <p style="margin: 0;"><strong>⚠️ Evaluación URGENTE: {{wave_index}}</strong></p>
                <p style="margin: 5px 0 0; font-size: 14px;">Tiempo estimado: <strong>{{estimated_time}} minutos</strong></p>
            </div>
            
            {{due_date_html}}
            
            <p class="urgent-text">Si no completas esta evaluación pronto, podríamos tener que cerrar tu participación en el estudio.</p>
            
            <p>Por favor, toma unos minutos para completarla ahora. Si necesitas ayuda, contáctanos inmediatamente.</p>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{magic_link}}" class="button">COMPLETAR EVALUACIÓN AHORA</a>
            </p>
            
            <p class="meta-info">Este enlace es único para ti y no requiere contraseña.</p>
        </div>
        <div class="footer">
            <p>Estudio: {{survey_name}}</p>
            <p>Investigador Principal: {{investigator_name}}</p>
            <p>Contacto: <a href="mailto:{{investigator_email}}" style="color: #666;">{{investigator_email}}</a></p>
        </div>
    </div>
</body>
</html>
