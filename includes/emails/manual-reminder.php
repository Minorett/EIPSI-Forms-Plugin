<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio: {{survey_name}}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f9f9f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px 20px; border-radius: 8px; margin-top: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #eeeeee; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #1a1a1a; font-size: 24px; font-weight: 600; }
        .header .reminder-badge { display: inline-block; background: #e67e22; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 10px; }
        .content { margin-bottom: 30px; font-size: 16px; }
        .highlight-box { background-color: #fef5e7; border-left: 4px solid #e67e22; padding: 15px; margin: 20px 0; border-radius: 0 4px 4px 0; }
        .custom-message-box { background-color: #f0f7fb; border: 1px dashed #3B6CAA; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .custom-message-box p { margin: 0; font-style: italic; color: #555; }
        .button { display: inline-block; background-color: #3B6CAA; color: #ffffff !important; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: 500; margin-top: 10px; text-align: center; font-size: 16px; width: 100%; box-sizing: border-box; }
        .button:hover { background-color: #005177; }
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
            <span class="reminder-badge">📬 Recordatorio Manual</span>
            <h1>{{survey_name}}</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{first_name}}</strong>,</p>
            
            <div class="highlight-box">
                <p style="margin: 0;"><strong>Tu próxima toma: {{wave_index}}</strong></p>
                <?php if (!empty($due_date) && $due_date !== 'Pronto'): ?>
                <p style="margin: 5px 0 0;">Fecha límite: {{due_date}}</p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($custom_message)): ?>
            <div class="custom-message-box">
                <p><strong>Mensaje del investigador:</strong></p>
                <p>{{custom_message}}</p>
            </div>
            <?php endif; ?>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{magic_link}}" class="button">Responder ahora</a>
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
