#!/usr/bin/env node
const fs = require('fs');
const path = require('path');

// Directorios a verificar
const PHP_DIRS = ['admin/', 'includes/', 'lib/', 'blocks/', 'src/'];
const JS_DIRS = ['assets/js/', 'src/', 'blocks/'];
const IGNORE_DIRS = ['build/', 'node_modules/', '.git/', 'languages/'];

// Función para buscar archivos recursivamente
function findFiles(dirs, extensions) {
    let files = [];
    dirs.forEach(dir => {
        if (fs.existsSync(dir)) {
            const items = fs.readdirSync(dir);
            items.forEach(item => {
                const fullPath = path.join(dir, item);
                if (IGNORE_DIRS.includes(item + '/')) return;

                if (fs.statSync(fullPath).isDirectory()) {
                    files = files.concat(findFiles([fullPath], extensions));
                } else if (extensions.includes(path.extname(item))) {
                    files.push(fullPath);
                }
            });
        }
    });
    return files;
}

// Función para extraer funciones PHP
function extractPhpFunctions(content, filePath) {
    const phpFunctions = [];
    const regex = /^\s*function\s+(\w+)\s*\(/gm;
    let match;

    while ((match = regex.exec(content)) !== null) {
        const line = content.substring(0, match.index).split('\n').length;
        phpFunctions.push({
            name: match[1],
            file: filePath,
            line: line
        });
    }

    return phpFunctions;
}

// Función para extraer funciones JavaScript
function extractJsFunctions(content, filePath) {
    const jsFunctions = [];
    const regexes = [
        /^\s*function\s+(\w+)\s*\(/gm, // function nombre()
        /^\s*const\s+(\w+)\s*=\s*function\s*\(/gm, // const nombre = function()
        /^\s*const\s+(\w+)\s*=\s*\([^=>]*\)\s*=>/gm // const nombre = () => {}
    ];

    regexes.forEach(regex => {
        let match;
        while ((match = regex.exec(content)) !== null) {
            const line = content.substring(0, match.index).split('\n').length;
            jsFunctions.push({
                name: match[1],
                file: filePath,
                line: line
            });
        }
    });

    return jsFunctions;
}

// Función para encontrar duplicados
function findDuplicates(functions) {
    const map = new Map();
    const duplicates = [];

    functions.forEach(func => {
        if (map.has(func.name)) {
            map.get(func.name).push(func);
        } else {
            map.set(func.name, [func]);
        }
    });

    map.forEach((funcs, name) => {
        if (funcs.length > 1) {
            duplicates.push({
                name: name,
                locations: funcs
            });
        }
    });

    return duplicates;
}

// Función para reportar duplicados
function reportDuplicates(duplicates, type) {
    duplicates.forEach(dup => {
        console.log(`  📍 ${dup.name} (${type})`);
        dup.locations.forEach(loc => {
            console.log(`     - ${loc.file}:${loc.line}`);
        });
        console.log('');
    });
}

// Función principal
function main() {
    console.log('🔍 Buscando funciones duplicadas...');

    // Buscar archivos PHP y JS
    const phpFiles = findFiles(PHP_DIRS, ['.php']);
    const jsFiles = findFiles(JS_DIRS, ['.js']);

    // Extraer funciones
    const phpFunctions = [];
    phpFiles.forEach(file => {
        const content = fs.readFileSync(file, 'utf8');
        phpFunctions.push(...extractPhpFunctions(content, file));
    });

    const jsFunctions = [];
    jsFiles.forEach(file => {
        const content = fs.readFileSync(file, 'utf8');
        jsFunctions.push(...extractJsFunctions(content, file));
    });

    // Buscar duplicados
    const phpDuplicates = findDuplicates(phpFunctions);
    const jsDuplicates = findDuplicates(jsFunctions);

    // Reportar resultados
    if (phpDuplicates.length === 0 && jsDuplicates.length === 0) {
        console.log(`✓ Verificación de funciones duplicadas completada\n  PHP: ${phpFunctions.length} funciones encontradas\n  JavaScript: ${jsFunctions.length} funciones encontradas\n  ✅ Sin duplicados detectados`);
        process.exit(0);
    } else {
        console.log('✗ Funciones duplicadas detectadas:\n');
        reportDuplicates(phpDuplicates, 'PHP');
        reportDuplicates(jsDuplicates, 'JavaScript');
        console.log('\nEjecuta: npm run lint:duplicates para más detalles');
        process.exit(1);
    }
}

// Ejecutar
main();