(() => {
    const shell = document.querySelector('.board-shell');
    if (!shell) {
        return;
    }

    const status = document.getElementById('save-status');
    const timers = new WeakMap();

    const setStatus = (message, state = '') => {
        if (!status) {
            return;
        }
        status.textContent = message;
        status.dataset.state = state;
    };

    const normalize = (value) => value.trim();

    const saveCell = async (input) => {
        const value = normalize(input.value);
        input.value = value;

        if ([...value].length > 3) {
            input.classList.add('has-error');
            setStatus('The cell may contain at most three characters.', 'error');
            return;
        }

        input.classList.remove('has-error');
        input.classList.add('is-saving');
        setStatus('Saving...', 'saving');

        try {
            const response = await fetch(shell.dataset.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': shell.dataset.csrfToken,
                },
                body: JSON.stringify({
                    team_id: Number(shell.dataset.teamId),
                    week_monday: shell.dataset.weekMonday,
                    user_id: Number(input.dataset.userId),
                    day: Number(input.dataset.day),
                    value,
                }),
            });
            const result = await response.json();

            if (!response.ok || !result.ok) {
                throw new Error(result.message || 'Could not save the cell.');
            }

            input.value = result.value || '';
            input.classList.remove('is-saving');
            input.classList.add('is-saved');
            setStatus('Saved.', 'saved');
            window.setTimeout(() => input.classList.remove('is-saved'), 800);
        } catch (error) {
            input.classList.remove('is-saving');
            input.classList.add('has-error');
            setStatus(error.message, 'error');
        }
    };

    document.querySelectorAll('.cell-input').forEach((input) => {
        input.addEventListener('input', () => {
            input.value = [...normalize(input.value)].slice(0, 3).join('');
            window.clearTimeout(timers.get(input));
            timers.set(input, window.setTimeout(() => saveCell(input), 350));
        });

        input.addEventListener('blur', () => {
            window.clearTimeout(timers.get(input));
            saveCell(input);
        });
    });
})();
