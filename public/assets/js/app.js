// Simple helper for GET/POST
async function httpGet(url){
  const res = await fetch(url, {headers:{'Accept':'application/json'}});
  if(!res.ok) throw new Error('Request failed');
  return res.json();
}
async function httpPost(url, data){
  const res = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify(data)});
  if(!res.ok) throw new Error('Request failed');
  return res.json();
}

// Activity ticker polling ("real-time" via algorithm)
let latestActivityId = 0;
async function pollActivities(){
  try{
    const data = await httpGet(`api/activities.php?since_id=${latestActivityId}`);
    if(Array.isArray(data.activities)){
      data.activities.forEach(a=>{
        latestActivityId = Math.max(latestActivityId, a.id);
        showToast(a);
      });
    }
  }catch(e){/* silent */}
  finally{ setTimeout(pollActivities, 3500); }
}

function showToast(activity){
  const wrap = document.querySelector('.ticker') || (()=>{const t=document.createElement('div');t.className='ticker';document.body.appendChild(t);return t;})();
  const el = document.createElement('div');
  el.className = 'toast';
  const title = activity.type === 'new_property' ? 'New listing' : activity.type === 'new_offer' ? 'New offer' : 'Price drop';
  const sub = activity.meta?.title ? `${activity.meta.title}` : '';
  const price = activity.meta?.price ? `Rs ${Number(activity.meta.price).toLocaleString('ne-NP')}` : '';
  el.innerHTML = `<div class="title">${title} ${price ? '· '+price : ''}</div><div class="sub">${sub}</div>`;
  wrap.appendChild(el);
  setTimeout(()=>{
    el.style.transform='translateY(8px)'; el.style.opacity='0';
    setTimeout(()=> el.remove(), 300);
  }, 5000);
}

// Budget estimator logic
function initEstimator(){
  const form = document.querySelector('#estimator');
  if(!form) return;
  const budget = form.querySelector('[name=budget]');
  const down = form.querySelector('[name=down]');
  const rate = form.querySelector('[name=rate]');
  const years = form.querySelector('[name=years]');
  const out = form.querySelector('#estimator-out');
  const rec = form.querySelector('#estimator-rec');

  function update(){
    const B = Number(budget.value || 0);
    const D = Number(down.value || 0);
    const r = (Number(rate.value || 10)) / 100 / 12;
    const n = (Number(years.value || 20)) * 12;
    const principal = Math.max(B - D, 0);
    const monthly = r>0 ? (principal * r) / (1 - Math.pow(1+r, -n)) : principal / n;
    out.textContent = `Estimated monthly: Rs ${Math.round(monthly).toLocaleString('ne-NP')}`;
    const target = Math.max(monthly*100, 50000);
    rec.textContent = `Recommended filter max price: Rs ${Math.round(target).toLocaleString('ne-NP')}`;
  }
  [budget,down,rate,years].forEach(i=> i.addEventListener('input', update));
  update();
}

// Live search
function initSearch(){
  const form = document.querySelector('#search');
  if(!form) return;
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const params = new URLSearchParams(new FormData(form));
    const data = await httpGet(`api/listings.php?${params.toString()}`);
    renderListings(data.results || []);
  });
}

function renderListings(items){
  const grid = document.querySelector('#listing-grid');
  if(!grid) return;
  grid.innerHTML = '';
  items.forEach(item=>{
    const el = document.createElement('a');
    el.href = `property.php?id=${item.id}`;
    el.className = 'card tilt';
    el.innerHTML = `
      <div class="media">
        <img src="${item.cover_image || 'assets/img/placeholder.svg'}" alt="${item.title}" style="width:100%;height:100%;object-fit:cover" onerror="this.onerror=null;this.src='assets/img/placeholder.svg'"/>
        <div class="chip">${item.type}</div>
      </div>
      <div class="body">
        <div class="price">Rs ${Number(item.price).toLocaleString('ne-NP')}</div>
        <div>${item.title}</div>
        <div class="meta">${item.district}${item.municipality? ', '+item.municipality:''}</div>
      </div>`;
    grid.appendChild(el);
  });
}

document.addEventListener('DOMContentLoaded', ()=>{
  initEstimator();
  initSearch();
  pollActivities();
});


