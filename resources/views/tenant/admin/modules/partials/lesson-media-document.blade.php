<div class="mt-3 space-y-4">
    <div class="lesson-dropzone" data-upload-root>
        <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-base-200/80 text-secondary">
                <i class="ph ph-file-pdf text-2xl" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-base-content">
                    {{ $doc?->file_path ? 'Sostituisci documento PDF' : 'Carica documento PDF' }}
                </p>
                <p class="mt-0.5 text-xs text-base-content/60">
                    PDF leggibile dai partecipanti (anteprima nella pagina lezione).
                </p>
                @if ($doc?->file_path)
                    <p class="mt-2 truncate text-xs font-medium text-base-content/70" title="{{ $doc->original_filename ?: basename($doc->file_path) }}">
                        {{ $doc->original_filename ?: basename($doc->file_path) }}
                    </p>
                @endif
            </div>
        </div>

        <form
            method="post"
            enctype="multipart/form-data"
            action="{{ route('tenant.admin.modules.lessons.document.upload', [$module, $lesson]) }}"
            class="mt-4 space-y-2"
            data-upload-form
            data-no-loader
        >
            @csrf
            <input
                type="file"
                name="document_file"
                accept=".pdf,application/pdf"
                class="file-input file-input-bordered file-input-sm w-full bg-base-100"
                required
            >
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    {{ $doc?->file_path ? 'Sostituisci PDF' : 'Carica PDF' }}
                </button>
            </div>
            <div class="hidden" data-upload-progress hidden>
                <progress class="progress progress-primary w-full" value="0" max="100" data-upload-progress-bar></progress>
                <p class="mt-1 text-xs tabular-nums text-base-content/60" data-upload-progress-label>0%</p>
            </div>
            <p class="min-h-[1rem] text-xs text-base-content/60" data-upload-status></p>
        </form>

        @error('document_file')
            <p class="mt-2 text-xs text-error">{{ $message }}</p>
        @enderror
    </div>
</div>
