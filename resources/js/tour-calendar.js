import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

// Available dates fetched from API
let validDates = [];
let dateFromPicker = null;
let dateToPicker = null;

/**
 * Initialize flatpickr calendar pickers.
 */
export function initTourCalendar() {
    const dateFromEl = document.getElementById('date_from');
    const dateToEl = document.getElementById('date_to');

    if (!dateFromEl || !dateToEl) return;

    const commonConfig = {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd.m.Y',
        minDate: 'today',
        locale: getLocaleConfig(),
        disableMobile: true,
    };

    dateFromPicker = flatpickr(dateFromEl, {
        ...commonConfig,
        defaultDate: dateFromEl.value || 'today',
        onChange: function (selectedDates) {
            if (selectedDates[0] && dateToPicker) {
                dateToPicker.set('minDate', selectedDates[0]);
            }
            updateNightsRange();
        },
    });

    dateToPicker = flatpickr(dateToEl, {
        ...commonConfig,
        defaultDate: dateToEl.value || null,
        onChange: function () {
            updateNightsRange();
        },
    });

    // Listen for country/city changes to fetch available dates
    const countrySelect = document.getElementById('country_id');
    const citySelect = document.getElementById('departure_city_id');

    if (countrySelect) {
        countrySelect.addEventListener('change', fetchAvailableDates);
    }
    if (citySelect) {
        citySelect.addEventListener('change', fetchAvailableDates);
    }
}

/**
 * Fetch available dates from the API.
 */
async function fetchAvailableDates() {
    const countryId = document.getElementById('country_id')?.value;
    const cityId = document.getElementById('departure_city_id')?.value;

    if (!countryId && !cityId) return;

    try {
        const params = new URLSearchParams();
        if (countryId) params.append('country_id', countryId);
        if (cityId) params.append('departure_city_id', cityId);

        const response = await fetch(`/api/tours/available-dates?${params}`);
        const data = await response.json();
        validDates = data.dates || [];

        // Update flatpickr with valid dates
        if (dateFromPicker && validDates.length > 0) {
            dateFromPicker.set('enable', validDates);
            dateToPicker.set('enable', validDates);
        } else if (dateFromPicker) {
            // No restriction if no valid dates found
            dateFromPicker.set('enable', undefined);
            dateToPicker.set('enable', undefined);
        }
    } catch (e) {
        console.error('Failed to fetch available dates:', e);
    }
}

/**
 * Fetch nights range from API and update the selects.
 */
async function updateNightsRange() {
    const countryId = document.getElementById('country_id')?.value;
    const dateFrom = document.getElementById('date_from')?.value;

    if (!countryId) return;

    try {
        const params = new URLSearchParams();
        if (countryId) params.append('country_id', countryId);
        if (dateFrom) params.append('date_from', dateFrom);

        const response = await fetch(`/api/tours/nights-range?${params}`);
        const data = await response.json();

        const nightsFrom = document.getElementById('nights_from');
        const nightsTo = document.getElementById('nights_to');

        if (nightsFrom && data.min) {
            nightsFrom.value = data.min;
        }
        if (nightsTo && data.max) {
            nightsTo.value = data.max;
        }
    } catch (e) {
        console.error('Failed to fetch nights range:', e);
    }
}

function getLocaleConfig() {
    const lang = document.documentElement.lang || 'en';

    if (lang.startsWith('ru')) {
        return {
            firstDayOfWeek: 1,
            weekdays: {
                shorthand: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                longhand: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
            },
            months: {
                shorthand: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
                longhand: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
            },
        };
    }

    return {};
}
