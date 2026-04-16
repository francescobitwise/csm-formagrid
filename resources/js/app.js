import './bootstrap';
import './scorm-api-adapter';
import { initPageLoader } from './loader';

initPageLoader();

if (document.querySelector('[data-video-direct-upload]')) {
    import('./video-direct-upload').then((m) => m.initVideoDirectUpload());
}
