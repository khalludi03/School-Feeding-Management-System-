const fs = require('fs');
const file = 'components/field/delivery-form.tsx';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(
  `} else if (oldAllocs && typeof oldAllocs === 'object') {
        initAllocations[item.id] = Object.values(oldAllocs).map((a: Record<string, unknown>) => ({
          date: a.date || '',
          quantity: Number(a.quantity) || 0,
        }));
      }`,
  `} else if (oldAllocs && typeof oldAllocs === 'object') {
        initAllocations[item.id] = Object.values(oldAllocs).map((a: Record<string, unknown>) => ({
          date: a.date || '',
          quantity: Number(a.quantity) || 0,
        }));
      } else {
        initAllocations[item.id] = [{ date: '', quantity: 0 }];
      }`
);

fs.writeFileSync(file, content);
