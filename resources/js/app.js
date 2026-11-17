const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const dataElement = $('#prototype-data');
const data = dataElement ? JSON.parse(dataElement.textContent) : {};
const money = value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
const localDate = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
let messageTimer;

function notify(message = 'Pré-visualização: as alterações ainda não são salvas.') {
    const activeDialog = $$('dialog[open]').at(-1);
    const element = activeDialog ? $('[data-dialog-message]', activeDialog) : $('#preview-message');
    element.textContent = message;
    element.hidden = false;
    clearTimeout(messageTimer);
    messageTimer = setTimeout(() => { element.hidden = true; }, 5000);
}

function openDialog(id) {
    const dialog = document.getElementById(id);
    if (dialog && !dialog.open) dialog.showModal();
}

$$('dialog').forEach(dialog => {
    dialog.addEventListener('close', () => { $('[data-dialog-message]', dialog).hidden = true; });
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const box = dialog.getBoundingClientRect();
        if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close();
    });
});

function authView(view) {
    $$('[data-auth-view]').forEach(element => { element.hidden = element.dataset.authView !== view; });
    $(`[data-auth-view="${view}"] input`)?.focus();
}

function editCatalog(kind, id) {
    const dialog = document.getElementById(`edit-${kind}`);
    const form = $('form', dialog);
    form.reset();
    const record = data[kind]?.find(item => item.id === id);
    const labels = { clients: 'cliente', professionals: 'profissional', services: 'serviço' };
    $('h2', dialog).textContent = `${record ? 'Editar' : 'Novo'} ${labels[kind]}`;
    $('[data-edit-only]', form).hidden = !record;
    if (record) {
        $$('[name]', form).forEach(input => {
            const value = record[input.name];
            if (input.type === 'checkbox') input.checked = Array.isArray(value) && value.includes(input.value);
            else if (input.type === 'radio') input.checked = input.value === value;
            else input.value = value === undefined ? '' : String(value);
        });
    }
    openDialog(dialog.id);
}

function updateCatalog(root) {
    const query = $('[data-search]', root).value.toLocaleLowerCase('pt-BR').trim();
    const showInactive = $('[data-toggle-inactive]', root).getAttribute('aria-pressed') === 'true';
    let count = 0;
    $$('[data-record]', root).forEach(record => {
        record.hidden = (!showInactive && record.dataset.active !== 'true') || !record.dataset.searchText.toLocaleLowerCase('pt-BR').includes(query);
        if (!record.hidden) count++;
    });
    $('[data-result-count]', root).textContent = `${count} registro(s)`;
    $('[data-empty]', root).hidden = count > 0;
}

$$('[data-catalog]').forEach(root => {
    updateCatalog(root);
    $('[data-search]', root).addEventListener('input', () => updateCatalog(root));
    $('[data-toggle-inactive]', root).addEventListener('click', event => {
        const button = event.currentTarget;
        const show = button.getAttribute('aria-pressed') !== 'true';
        button.setAttribute('aria-pressed', String(show));
        button.textContent = show ? 'Ocultar inativos' : 'Mostrar inativos';
        updateCatalog(root);
    });
});

const appointmentList = $('[data-appointment-list]');
if (appointmentList) {
    const filter = () => {
        const query = $('[data-search]', appointmentList).value.toLocaleLowerCase('pt-BR').trim();
        const status = $('[data-status-filter]', appointmentList).value;
        const professional = $('[data-professional-filter]', appointmentList).value;
        const period = Number($('[data-period-filter]', appointmentList).value);
        const today = new Date();
        const end = new Date(today);
        end.setDate(end.getDate() + period);
        let count = 0;
        $$('[data-record]', appointmentList).forEach(record => {
            record.hidden = !record.dataset.searchText.toLocaleLowerCase('pt-BR').includes(query)
                || (status && record.dataset.status !== status)
                || (professional && !record.dataset.professionals.split(',').includes(professional))
                || (period && (record.dataset.date < localDate(today) || record.dataset.date >= localDate(end)));
            if (!record.hidden) count++;
        });
        $('[data-empty]', appointmentList).hidden = count > 0;
    };
    appointmentList.addEventListener('input', filter);
    appointmentList.addEventListener('change', filter);
}

function updateItems() {
    const form = $('[data-preview-form="appointment"]');
    if (!form) return;
    const items = $$('[data-appointment-item]', form);
    let total = 0;
    items.forEach((item, index) => {
        $('[data-item-label]', item).textContent = `Item ${index + 1}`;
        $('[data-remove-item]', item).hidden = items.length === 1;
        const service = data.services.find(service => service.id === $('[data-item-service]', item).value);
        const professional = $('[data-item-professional]', item);
        $$('option', professional).forEach(option => {
            const allowed = !option.value || !service || service.professionalIds.includes(option.value);
            option.disabled = !allowed;
            option.hidden = !allowed;
        });
        if (service && !service.professionalIds.includes(professional.value)) professional.value = '';
        const start = $('[data-item-start]', item).value;
        $('[data-item-summary]', item).hidden = !service || !start;
        if (service) {
            total += service.price;
            $('[data-duration]', item).textContent = `${service.durationMinutes} min`;
            $('[data-price]', item).textContent = money(service.price);
            if (start) {
                const end = new Date(new Date(start).getTime() + service.durationMinutes * 60000);
                $('[data-end]', item).textContent = end.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            }
        }
    });
    $('[data-item-count]', form).textContent = `${items.length} item(s)`;
    $('[data-total]', form).textContent = money(total);
}

function addItem(values = {}) {
    const fragment = $('#appointment-item-template').content.cloneNode(true);
    $$('[name]', fragment).forEach(input => {
        if (values[input.name] !== undefined) input.value = values[input.name];
    });
    $('[data-appointment-items]').append(fragment);
    updateItems();
}

function newAppointment({ appointment, date } = {}) {
    const dialog = $('#new-appointment');
    const form = $('form', dialog);
    form.reset();
    $('[data-appointment-items]', form).replaceChildren();
    $('h2', dialog).textContent = appointment ? 'Reagendar atendimento' : 'Novo agendamento';
    $('[data-submit-label]', form).textContent = appointment ? 'Salvar reagendamento' : 'Criar agendamento';
    $('[data-reschedule-note]', form).hidden = !appointment;
    $('[data-form-error]', form).hidden = true;
    if (appointment) {
        form.elements.clientId.value = appointment.clientId;
        form.elements.notes.value = appointment.notes ?? '';
        appointment.items.forEach(item => addItem({ ...item, startTime: item.startTime.slice(0, 16) }));
    } else addItem({ startTime: date ?? '' });
    openDialog('new-appointment');
}

$('[data-preview-form="appointment"]')?.addEventListener('change', updateItems);

const realAppointmentForm = $('[data-real-appointment-form]');
if (realAppointmentForm) {
    let nextItemIndex = 0;
    realAppointmentForm.addEventListener('click', event => {
        if (event.target.closest('[data-real-add-item]')) {
            const fragment = $('#real-appointment-item-template').content.cloneNode(true);
            $$('[name]', fragment).forEach(input => { input.name = input.name.replace('__INDEX__', `new-${nextItemIndex}`); });
            nextItemIndex++;
            $('[data-real-appointment-items]', realAppointmentForm).append(fragment);
        } else if (event.target.closest('[data-real-remove-item]')) {
            event.target.closest('[data-real-appointment-item]').remove();
        }
    });
}

function syncAvailability(fieldset) {
    const active = $('[data-day-enabled]', fieldset).checked;
    $('[data-day-off]', fieldset).hidden = active;
    $('[data-add-interval]', fieldset).hidden = !active;
    $('[data-intervals]', fieldset).hidden = !active;
    $$('[data-interval] input', fieldset).forEach(input => { input.disabled = !active; });
}

let availabilityIntervalIndex = 0;
function addInterval(fieldset) {
    const fragment = $('#interval-template').content.cloneNode(true);
    if (fieldset.closest('[data-availability-form]')) {
        const interval = $('[data-interval]', fragment);
        const prefix = `intervals[${fieldset.dataset.dayIndex}][new-${availabilityIntervalIndex++}]`;
        $('[data-time-start]', interval).name = `${prefix}[starts_at]`;
        $('[data-time-end]', interval).name = `${prefix}[ends_at]`;
    }
    $('[data-intervals]', fieldset).append(fragment);
}

$$('[data-day-enabled]').forEach(input => input.addEventListener('change', () => {
    const fieldset = input.closest('[data-availability-day]');
    if (input.checked && !$$('[data-interval]', fieldset).length) addInterval(fieldset);
    syncAvailability(fieldset);
}));

const selectedProfessionals = new Set();
function filterAgenda() {
    $$('[data-agenda-filter]').forEach(button => {
        const selected = selectedProfessionals.size === 0 || selectedProfessionals.has(button.dataset.agendaFilter);
        button.setAttribute('aria-pressed', String(selected));
        button.style.opacity = selected ? '1' : '.45';
    });
    $('[data-clear-agenda-filter]').hidden = selectedProfessionals.size === 0;
    $$('[data-block-professional]').forEach(block => { block.hidden = selectedProfessionals.size > 0 && !selectedProfessionals.has(block.dataset.blockProfessional); });
    $$('[data-unavailable]').forEach(overlay => { overlay.hidden = selectedProfessionals.size !== 1 || !selectedProfessionals.has(overlay.dataset.unavailable); });
    $$('.calendar-day').forEach(day => {
        const ends = [];
        const cards = $$('[data-calendar-card]', day);
        cards.forEach(card => {
            card.hidden = selectedProfessionals.size > 0 && !selectedProfessionals.has(card.dataset.professional);
            if (card.hidden) return;
            const start = Number(card.dataset.start);
            let column = ends.findIndex(end => end <= start);
            if (column === -1) column = ends.length;
            ends[column] = start + Number(card.dataset.duration);
            card.dataset.column = column;
        });
        cards.filter(card => !card.hidden).forEach(card => {
            card.style.left = `calc(${Number(card.dataset.column) / ends.length * 100}% + 2px)`;
            card.style.width = `calc(${100 / ends.length}% - 4px)`;
        });
    });
}

const mobile = matchMedia('(max-width: 767px)');
function dateNavigation() {
    $$('[data-date-navigation]').forEach(link => {
        const url = new URL(link.href);
        url.searchParams.set('date', mobile.matches ? link.dataset.mobileDate : link.dataset.desktopDate);
        link.href = url.toString();
    });
}
dateNavigation();
mobile.addEventListener('change', dateNavigation);

$$('.calendar-day').forEach(day => day.addEventListener('click', event => {
    if (event.target.closest('[data-calendar-card]')) return;
    const agenda = $('[data-agenda]');
    const calendarStart = Number(agenda?.dataset.calendarStart ?? 480);
    const calendarEnd = Number(agenda?.dataset.calendarEnd ?? 1260);
    const minutes = Math.min(calendarEnd - 30, Math.max(calendarStart, Math.floor((event.clientY - day.getBoundingClientRect().top) / 32) * 30 + calendarStart));
    const hour = String(Math.floor(minutes / 60)).padStart(2, '0');
    const minute = String(minutes % 60).padStart(2, '0');
    const start = `${day.dataset.day}T${hour}:${minute}`;
    const createUrl = $('[data-agenda]')?.dataset.createAppointmentUrl;
    if (createUrl) {
        const url = new URL(createUrl, window.location.href);
        url.searchParams.set('starts_at', start);
        window.location.assign(url.toString());
    } else newAppointment({ date: start });
}));

function offlineBanner() {
    $$('[data-offline]').forEach(banner => banner.classList.toggle('hidden', navigator.onLine));
}
offlineBanner();
window.addEventListener('online', offlineBanner);
window.addEventListener('offline', offlineBanner);

document.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button) return;
    if (button.hasAttribute('data-close')) button.closest('dialog')?.close();
    else if (button.dataset.open) openDialog(button.dataset.open);
    else if (button.dataset.authTarget) authView(button.dataset.authTarget);
    else if (button.hasAttribute('data-toggle-password')) {
        const input = button.parentElement.querySelector('input');
        input.type = input.type === 'password' ? 'text' : 'password';
        button.setAttribute('aria-label', input.type === 'password' ? 'Mostrar senha' : 'Ocultar senha');
        button.setAttribute('aria-pressed', String(input.type === 'text'));
    } else if (button.dataset.edit) editCatalog(button.dataset.edit, button.dataset.id);
    else if (button.hasAttribute('data-new-appointment')) newAppointment();
    else if (button.dataset.reschedule) {
        const appointment = data.appointments.find(item => item.id === button.dataset.reschedule);
        button.closest('dialog')?.close();
        newAppointment({ appointment });
    } else if (button.hasAttribute('data-add-item')) addItem();
    else if (button.hasAttribute('data-remove-item')) {
        button.closest('[data-appointment-item]').remove();
        updateItems();
    } else if (button.dataset.confirm) {
        const dialog = $('#confirm-action');
        $('h2', dialog).textContent = button.dataset.confirm;
        $('[data-confirm-message]', dialog).textContent = button.dataset.confirmMessage;
        $('[data-cancellation-reason]', dialog).hidden = !button.hasAttribute('data-cancel-appointment');
        $('textarea', dialog).value = '';
        openDialog(dialog.id);
    } else if (button.hasAttribute('data-preview-action')) {
        if (button.closest('dialog')?.id === 'confirm-action') button.closest('dialog').close();
        notify();
    } else if (button.hasAttribute('data-close-details')) button.closest('details').open = false;
    else if (button.hasAttribute('data-add-interval')) addInterval(button.closest('[data-availability-day]'));
    else if (button.hasAttribute('data-remove-interval')) {
        const fieldset = button.closest('[data-availability-day]');
        button.closest('[data-interval]').remove();
        if (!$$('[data-interval]', fieldset).length) {
            $('[data-day-enabled]', fieldset).checked = false;
            syncAvailability(fieldset);
        }
    } else if (button.dataset.agendaFilter) {
        const id = button.dataset.agendaFilter;
        if (selectedProfessionals.has(id)) selectedProfessionals.delete(id);
        else selectedProfessionals.add(id);
        filterAgenda();
    } else if (button.hasAttribute('data-clear-agenda-filter')) {
        selectedProfessionals.clear();
        filterAgenda();
    }
});

document.addEventListener('submit', event => {
    const form = event.target.closest('[data-preview-form]');
    if (!form) return;
    event.preventDefault();
    if (!navigator.onLine) return notify('Sem conexão. Tente novamente quando estiver online.');
    if (form.dataset.previewForm === 'login') {
        window.location.assign(form.dataset.destination);
    } else if (form.dataset.previewForm === 'forgot') {
        $('[data-forgot-email]').textContent = form.elements.email.value;
        authView('sent');
    } else if (form.dataset.previewForm === 'block' && form.elements.endTime.value <= form.elements.startTime.value) {
        notify('O término deve ser posterior ao início.');
    } else if (form.dataset.previewForm === 'availability') {
        const invalid = $$('[data-interval]', form).some(interval => {
            const [start, end] = $$('input', interval);
            return !start.disabled && end.value <= start.value;
        });
        notify(invalid ? 'O término de cada intervalo deve ser posterior ao início.' : undefined);
    } else notify();
});
