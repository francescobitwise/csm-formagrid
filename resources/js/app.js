import './bootstrap';
import './scorm-api-adapter';
import { initPageLoader } from './loader';
import { initUiModals } from './ui-modal';

initPageLoader();
initUiModals();

if (document.querySelector('[data-video-direct-upload]')) {
    import('./video-direct-upload').then((m) => m.initVideoDirectUpload());
}

if (document.querySelector('[data-bulk-lesson-upload]')) {
    import('./bulk-lesson-upload').then((m) => m.initBulkLessonUpload());
}

if (document.querySelector('form[data-upload-form]')) {
    import('./form-upload-progress').then((m) => m.initFormUploadProgress());
}
