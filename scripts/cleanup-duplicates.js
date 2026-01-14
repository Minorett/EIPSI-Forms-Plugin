#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Mapeo de funciones duplicadas a eliminar de cada archivo
const duplicateFunctions = {
    // Campo descripción
    'src/blocks/campo-descripcion/save.js': ['renderDescriptionBody'],
    'src/blocks/campo-descripcion/edit.js': ['renderDescriptionBody'],
    
    // Campo likert
    'src/blocks/campo-likert/edit.js': ['renderHelperText', 'getFieldId', 'calculateMaxValue'],
    'src/blocks/campo-likert/save.js': ['renderHelperText', 'getFieldId', 'calculateMaxValue'],
    
    // Campo múltiple
    'src/blocks/campo-multiple/edit.js': ['renderHelperText', 'getFieldId'],
    'src/blocks/campo-multiple/save.js': ['renderHelperText', 'getFieldId'],
    
    // Campo radio
    'src/blocks/campo-radio/edit.js': ['renderHelperText', 'getFieldId'],
    'src/blocks/campo-radio/save.js': ['renderHelperText', 'getFieldId'],
    
    // Campo select
    'src/blocks/campo-select/edit.js': ['renderHelperText', 'getFieldId'],
    'src/blocks/campo-select/save.js': ['renderHelperText', 'getFieldId'],
    
    // Campo textarea
    'src/blocks/campo-textarea/edit.js': ['renderHelperText', 'getFieldId'],
    'src/blocks/campo-textarea/save.js': ['renderHelperText', 'getFieldId'],
    
    // Campo texto
    'src/blocks/campo-texto/edit.js': ['renderHelperText', 'getFieldId'],
    'src/blocks/campo-texto/save.js': ['renderHelperText', 'getFieldId'],
    
    // VAS slider
    'src/blocks/vas-slider/edit.js': ['renderHelperText', 'getFieldId'],
    'src/blocks/vas-slider/save.js': ['renderHelperText', 'getFieldId'],
    
    // Consent block
    'src/blocks/consent-block/edit.js': ['renderConsentBody'],
    'src/blocks/consent-block/save.js': ['renderConsentBody']
};

// Funciones para limpiar cada tipo de archivo
function cleanFunctions(content, functionsToRemove) {
    let cleaned = content;
    
    functionsToRemove.forEach(funcName => {
        // Patrón para encontrar la función completa
        const patterns = [
            // const nombre = function
            new RegExp(`const\\s+${funcName}\\s*=\\s*function[\\s\\S]*?};\\s*\\n?`, 'g'),
            // function nombre
            new RegExp(`function\\s+${funcName}\\s*\\([\\s\\S]*?\\}\\s*\\n?`, 'g'),
            // const nombre = () =>
            new RegExp(`const\\s+${funcName}\\s*=\\s*\\([^)]*\\)\\s*=>\\s*\\{[\\s\\S]*?\\}\\s*\\n?`, 'g')
        ];
        
        patterns.forEach(pattern => {
            cleaned = cleaned.replace(pattern, '');
        });
    });
    
    return cleaned;
}

// Función para agregar imports necesarios
function addImports(content, filename) {
    let cleaned = content;
    
    // Verificar si ya existe el import de utils
    if (!cleaned.includes('from \'../../utils/helpers\'') && !cleaned.includes('from \'../utils/helpers\'')) {
        const utilsImport = "import { renderHelperText, getFieldId, calculateMaxValue, renderDescriptionBody, renderConsentBody } from '../../utils/helpers';";
        
        // Buscar la línea de imports existentes
        const lines = cleaned.split('\n');
        let insertIndex = -1;
        
        for (let i = 0; i < lines.length; i++) {
            if (lines[i].trim().startsWith('import') && lines[i].includes('from')) {
                insertIndex = i + 1;
            }
        }
        
        if (insertIndex !== -1) {
            lines.splice(insertIndex, 0, utilsImport);
            cleaned = lines.join('\n');
        } else {
            cleaned = utilsImport + '\n' + cleaned;
        }
    }
    
    return cleaned;
}

// Función principal
function main() {
    console.log('🧹 Limpiando funciones duplicadas...');
    
    let cleaned = 0;
    let errors = 0;
    
    Object.entries(duplicateFunctions).forEach(([filename, functions]) => {
        const filepath = path.join(process.cwd(), filename);
        
        if (!fs.existsSync(filepath)) {
            console.log(`⚠️  Archivo no encontrado: ${filename}`);
            return;
        }
        
        try {
            let content = fs.readFileSync(filepath, 'utf8');
            console.log(`📄 Procesando: ${filename}`);
            
            // Agregar imports
            content = addImports(content, filename);
            
            // Limpiar funciones duplicadas
            content = cleanFunctions(content, functions);
            
            // Escribir archivo
            fs.writeFileSync(filepath, content, 'utf8');
            cleaned++;
            console.log(`✅ Limpiado: ${filename} (${functions.length} funciones eliminadas)`);
            
        } catch (error) {
            console.log(`❌ Error procesando ${filename}:`, error.message);
            errors++;
        }
    });
    
    console.log(`\n🎉 Limpieza completada:`);
    console.log(`   ✅ Archivos limpiados: ${cleaned}`);
    console.log(`   ❌ Errores: ${errors}`);
}

// Ejecutar
main();
