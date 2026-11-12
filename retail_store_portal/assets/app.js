(() => {
  const form = document.querySelector('form[data-lines]');
  if (!form) return;
  const list = document.getElementById('line-items');
  const add = document.getElementById('add-line');
  const template = list.querySelector('.line-item').cloneNode(true);
  const isSale = form.dataset.lines === 'sale';
  const format = num => 'Rs. ' + num.toLocaleString('en-LK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const update = () => {
    let total = 0;
    list.querySelectorAll('.line-item').forEach(row => {
      const select = row.querySelector('.product-choice');
      const qty = Number(row.querySelector('input[name="quantity[]"]').value || 0);
      const opt = select.selectedOptions[0];
      const unit = isSale ? Number(opt?.dataset.price || 0) : Number(row.querySelector('input[name="unit_cost[]"]')?.value || 0);
      total += qty * unit;
      const hint = row.querySelector('.line-hint');
      if (hint) hint.textContent = select.value ? `Available: ${opt.dataset.stock} units · Price: ${format(unit)}` : '';
    });
    const field = document.getElementById('sale-total');
    if (field) field.textContent = format(total);
  };
  add?.addEventListener('click', () => {
    if (list.children.length >= 50) { alert('Maximum 50 items per transaction.'); return; }
    const row = template.cloneNode(true);
    row.querySelectorAll('input').forEach(input => { input.value = input.name === 'quantity[]' ? '1' : '0.00'; });
    row.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
    const hint = row.querySelector('.line-hint'); if (hint) hint.textContent = '';
    list.appendChild(row); update();
  });
  list.addEventListener('click', e => {
    const remove = e.target.closest('.remove-line');
    if (remove && list.children.length > 1) { remove.closest('.line-item').remove(); update(); }
  });
  list.addEventListener('change', update);
  list.addEventListener('input', update);
  form.addEventListener('submit', e => {
    const selected = [...list.querySelectorAll('.product-choice')].map(s => s.value);
    if (selected.includes('') || new Set(selected).size !== selected.length) {
      e.preventDefault(); alert('Choose a product for each row and do not repeat a product.'); return;
    }
    if (isSale) {
      const over = [...list.querySelectorAll('.line-item')].some(row => {
        const select = row.querySelector('.product-choice');
        return Number(row.querySelector('input[name="quantity[]"]').value) > Number(select.selectedOptions[0]?.dataset.stock || 0);
      });
      if (over) { e.preventDefault(); alert('A sale quantity exceeds available stock.'); }
    }
  });
  update();
})();
