<?php
// includes/componentes.php - Piezas de HTML reutilizables

/**
 * Barra de progreso. PHP solo imprime los datos; JavaScript (main.js)
 * calcula el porcentaje, anima el ancho y cambia el color:
 *   modo "presupuesto": verde < 80 %, amarillo >= 80 %, rojo >= 100 %
 *   modo "meta":        azul < 100 %, verde al completarla
 */
function barra_progreso(float $actual, float $limite, string $modo = 'presupuesto'): string
{
    return sprintf(
        '<div class="progreso" data-actual="%s" data-limite="%s" data-modo="%s">
           <div class="barra-progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100">
             <div class="barra-relleno"></div>
           </div>
           <span class="progreso-porcentaje">0%%</span>
         </div>',
        $actual,
        $limite,
        e($modo)
    );
}
