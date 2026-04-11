<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ÚLTIMO recordatorio - {{survey_name}}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f9f9f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px 20px; border-radius: 8px; margin-top: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(244,67,54,0.2); border: 2px solid #f44336; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #eeeeee; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #b71c1c; font-size: 24px; font-weight: 700; }
        .content { margin-bottom: 30px; font-size: 16px; }
        .highlight-box { background-color: #ffebee; border-left: 4px solid #b71c1c; padding: 20px; margin: 20px 0; border-radius: 0 4px 4px 0; }
        .final-notice { background-color: #b71c1c; color: white; padding: 15px; text-align: center; border-radius: 4px; margin: 20px 0; font-weight: 600; }
        .due-box { background-color: #ffebee; border-left: 4px solid #b71c1c; padding: 15px; margin: 20px 0; border-radius: 0 4px 4px 0; }
        .urgent-text { color: #b71c1c; font-weight: 500; }
        .button { display: inline-block; background-color: #b71c1c; color: #ffffff !important; padding: 16px 32px; text-decoration: none; border-radius: 4px; font-weight: 700; margin-top: 10px; text-align: center; font-size: 18px; width: 100%; box-sizing: border-box; text-transform: uppercase; }
        .button:hover { background-color: #7f0000; }
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
            <h1>🚨 ÚLTIMO RECORDATORIO</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{first_name}}</strong>,</p>
            
            <div class="final-notice">
                Esta es tu última oportunidad para completar la evaluación
            </div>
            
            <p class="urgent-text">Hemos intentado contactarte varias veces sobre tu evaluación pendiente. Desafortunadamente, si no la completas ahora, tendremos que dar por terminada tu participación en esta toma del estudio.</p>
            
            <div class="highlight-box">
                <p style="margin: 0; color: #b71c1c;"><strong>🚨 EVALUACIÓN CRÍTICA: {{wave_index}}</strong></p>
                <p style="margin: 5px 0 0; font-size: 14px;">Tiempo estimado: <strong>{{estimated_time}} minutos</strong></p>
            </div>
            
            {{due_date_html}}
            
            <p class="urgent-text">Valoramos enormemente tu participación y nos encantaría poder incluir tus datos. Por favor, completa la evaluación ahora mismo.</p>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{magic_link}}" class="button">COMPLETAR AHORA - ÚLTIMA OPORTUNIDAD</a>
            </p>
            
            <p class="meta-info">Este enlace es único para ti y no requiere contraseña.<br>Si no puedes completarla ahora, por favor contáctanos urgentemente.</p>
        </div>
        <div class="footer">
            <p>Estudio: {{survey_name}}</p>
            <p>Investigador Principal: {{investigator_name}}</p>
            <p>Contacto URGENTE: <a href="mailto:{{investigator_email}}" style="color: #b71c1c; font-weight: 600;">{{investigator_email}}</a></p>
        </div>
    </div>
</body>
</html>
