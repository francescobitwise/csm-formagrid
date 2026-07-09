// Chiavi che segnano completamento/esito: vanno salvate subito, senza debounce,
// altrimenti si perdono se l'utente chiude la pagina appena finito il modulo.
const CRITICAL_CMI_KEYS = new Set([
    "cmi.core.lesson_status",
    "cmi.completion_status",
    "cmi.success_status",
    "cmi.core.exit",
    "cmi.exit",
]);

function createScormRuntime(config) {
    const state = {
        queue: {},
        // Riprende lo stato salvato (suspend_data, lesson_status, ...) così il
        // pacchetto può fare resume invece di ripartire da zero a ogni apertura.
        localState: { ...config.initialCmi },
        flushTimer: null,
        initialized: false,
    };

    const flush = (event = null) => {
        const payload = {
            package_id: config.packageId,
            enrollment_id: config.enrollmentId,
            data: { ...state.queue },
        };

        if (event) {
            payload.data.__event = event;
        }

        const hasData = Object.keys(payload.data).length > 0;
        if (!hasData) return Promise.resolve();

        state.queue = {};

        return fetch("/api/scorm/track", {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": config.csrfToken,
                "X-Skip-Loader": "1",
            },
            body: JSON.stringify(payload),
            credentials: "same-origin",
            // Consente alla richiesta di sopravvivere alla chiusura/navigazione
            // della pagina (fondamentale per il commit finale del SCO).
            keepalive: true,
        }).catch(() => {
            // Don't break SCO runtime on transient network failures.
        });
    };

    const scheduleFlush = () => {
        if (state.flushTimer) return;
        state.flushTimer = globalThis.setTimeout(() => {
            state.flushTimer = null;
            void flush();
        }, 5000);
    };

    const setValue = (key, value) => {
        state.localState[key] = value;
        state.queue[key] = value;
        if (CRITICAL_CMI_KEYS.has(key)) {
            void flush();
        } else {
            scheduleFlush();
        }
        return "true";
    };

    // Default per gli elementi read-only più comuni: alcuni player (iSpring,
    // Articulate) leggono mode/credit/entry all'avvio e senza valori sensati
    // non attivano il resume o il reporting del progresso.
    const READ_DEFAULTS = {
        "cmi.core.lesson_status": "not attempted",
        "cmi.completion_status": "unknown",
        "cmi.success_status": "unknown",
        "cmi.core.lesson_mode": "normal",
        "cmi.mode": "normal",
        "cmi.core.credit": "credit",
        "cmi.credit": "credit",
    };

    const getValue = (key) => {
        if (key in state.localState) {
            return state.localState[key] ?? "";
        }
        if (key === "cmi.core.entry" || key === "cmi.entry") {
            return state.localState["cmi.suspend_data"] ? "resume" : "ab-initio";
        }
        return READ_DEFAULTS[key] ?? "";
    };

    const runtime12 = {
        LMSInitialize: () => {
            state.initialized = true;
            state.queue["cmi.core.lesson_status"] = state.localState["cmi.core.lesson_status"] ?? "incomplete";
            void flush("initialize");
            return "true";
        },
        LMSFinish: () => {
            void flush("finish");
            return "true";
        },
        LMSGetValue: (key) => getValue(key),
        LMSSetValue: (key, value) => setValue(key, value),
        LMSCommit: () => {
            void flush("commit");
            return "true";
        },
        LMSGetLastError: () => "0",
        LMSGetErrorString: () => "",
        LMSGetDiagnostic: () => "",
    };

    const runtime2004 = {
        Initialize: () => runtime12.LMSInitialize(),
        Terminate: () => runtime12.LMSFinish(),
        GetValue: (key) => getValue(key),
        SetValue: (key, value) => setValue(key, value),
        Commit: () => runtime12.LMSCommit(),
        GetLastError: () => "0",
        GetErrorString: () => "",
        GetDiagnostic: () => "",
    };

    globalThis.API = runtime12;
    globalThis.API_1484_11 = runtime2004;

    // Ensure pending queue flushes when page is closed.
    globalThis.addEventListener("beforeunload", () => {
        void flush("beforeunload");
    });
}

globalThis.initScormRuntime = function initScormRuntime(config) {
    if (!config?.packageId || !config?.enrollmentId || !config?.csrfToken) {
        return;
    }
    createScormRuntime(config);
};

