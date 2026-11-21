/*
 * نقطة الدخول للأصول.
 *
 * driver.js محزوم هنا مع Vite — **مش من CDN**، عشان التطبيق يشتغل
 * في بيئة مقفولة من غير إنترنت (docs/15 بند ٥).
 */
import '../../packages/future-code/filament-onboarding/resources/js/tour.js';

// نصوص الجولة بتتحقن من الـ Blade في <head> — الملف ده بيقرا منها بس.
window.fcTourTranslations = window.fcTourTranslations ?? {};
