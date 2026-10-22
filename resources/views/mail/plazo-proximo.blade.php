<x-mail::message>
# Plazo próximo

Se acerca un evento en la agenda del despacho.

**Título:** {{ $titulo }}  
**Tipo:** {{ $tipo }}  
**Inicio:** {{ $inicio }}  
@if($lugar)
**Lugar:** {{ $lugar }}  
@endif
@if($expediente)
**Expediente:** {{ $expediente }}  
@endif
@if($notas)
**Notas:** {{ $notas }}  
@endif

Revisá la agenda para confirmar o actualizar el estado.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
