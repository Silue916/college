(() => {
  const form = document.querySelector('[data-preinscription-form]');
  if (!form) return;
  const steps = [...form.querySelectorAll('[data-step]')];
  const indicator = form.querySelector('[data-step-indicator]');
  const previous = form.querySelector('[data-step-previous]');
  const next = form.querySelector('[data-step-next]');
  const submit = form.querySelector('[data-step-submit]');
  const labels = ['Élève', 'Responsable légal', 'Pièces jointes'];
  let current = Math.max(1, Math.min(steps.length, Number(form.dataset.currentStep) || 1));
  form.noValidate = true;
  document.documentElement.classList.add('js-enabled');

  const showStep = (number) => {
    current = number;
    steps.forEach((step) => { step.hidden = Number(step.dataset.step) !== current; });
    indicator.textContent = `Étape ${current} sur ${steps.length} — ${labels[current - 1]}`;
    previous.hidden = current === 1;
    next.hidden = current === steps.length;
    submit.hidden = current !== steps.length;
  };
  const validateCurrent = () => {
    for (const field of steps[current - 1].querySelectorAll('input, select, textarea')) {
      if (!field.checkValidity()) {
        field.reportValidity();
        return false;
      }
    }
    return true;
  };

  form.querySelector('[data-niveau-select]').addEventListener('change', (event) => {
    const selected = event.target.selectedOptions[0];
    const secondaryCycle = selected?.dataset.cycle === '2';
    const series = form.querySelector('[data-serie-select]');
    const levelCode = selected?.dataset.code;
    const allowedSeries = levelCode === '2NDE'
      ? ['A', 'C']
      : ['1ERE', 'TLE'].includes(levelCode) ? ['A1', 'A2', 'C', 'D'] : [];
    series.required = secondaryCycle;
    series.disabled = !secondaryCycle;
    for (const option of series.options) {
      const allowed = option.value === '' || allowedSeries.includes(option.dataset.code);
      option.hidden = !allowed;
      option.disabled = !allowed;
      if (!allowed && option.selected) series.value = '';
    }
  });
  form.querySelector('[data-niveau-select]').dispatchEvent(new Event('change'));
  previous.addEventListener('click', () => showStep(current - 1));
  next.addEventListener('click', () => {
    if (validateCurrent()) showStep(current + 1);
  });
  form.addEventListener('submit', (event) => {
    for (const [index, step] of steps.entries()) {
      for (const field of step.querySelectorAll('input, select, textarea')) {
        if (!field.checkValidity()) {
          event.preventDefault();
          showStep(index + 1);
          field.reportValidity();
          return;
        }
      }
    }
  });
  showStep(current);
})();
