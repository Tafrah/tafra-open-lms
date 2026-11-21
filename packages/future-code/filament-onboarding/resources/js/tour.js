import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

/**
 * مشغّل الجولات.
 *
 * driver.js مفيهاش خيار rtl موثّق — الاتجاه شغلنا إحنا عبر الفئة
 * `.fc-tour--rtl` في ملف الثيم (docs/15 بند ٥).
 */
window.fcStartTour = (steps, { rtl = false, onDone = null } = {}) => {
    // العنصر اللي مش موجود بتتخطى خطوته بصمت بدل ما تكسر الجولة
    const present = steps.filter((step) => document.querySelector(step.element));

    if (! present.length) {
        return;
    }

    const translations = window.fcTourTranslations ?? {};

    const tour = driver({
        showProgress: true,
        allowClose: true,
        nextBtnText: translations.next ?? 'Next',
        prevBtnText: translations.prev ?? 'Back',
        doneBtnText: translations.done ?? 'Done',
        progressText: translations.progress ?? '{{current}} / {{total}}',
        popoverClass: rtl ? 'fc-tour fc-tour--rtl' : 'fc-tour',
        steps: present,
        /*
         * ⚠️ `onDestroyStarted` مش `onDestroyed`: في driver.js 1.8 الإغلاق
         * (زرار ×، Esc، «تم») مابينادّيش `onDestroyed` — الجولة كانت بتتقفل
         * ومابتتسجّلش، وبترجع مع كل زيارة. رحلة المتصفح هي اللي كشفتها
         * (docs/21 بند ٣٠). لما الـ hook ده موجود، الإغلاق نفسه مسؤوليتنا.
         */
        onDestroyStarted: () => {
            if (typeof onDone === 'function') {
                onDone();
            }

            tour.destroy();
        },
    });

    tour.drive();
};
