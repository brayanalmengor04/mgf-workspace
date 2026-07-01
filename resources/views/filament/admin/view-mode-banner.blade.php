@if (\App\Support\AdminViewMode::isProviderPreview())
    <div class="fi-admin-preview-banner">
        <x-filament::callout
            color="warning"
            icon="heroicon-o-exclamation-circle"
            heading="Vista previa de proveedor"
            description="Tus permisos de administrador siguen activos."
            class="fi-admin-preview-banner-callout"
        />
    </div>
@endif
