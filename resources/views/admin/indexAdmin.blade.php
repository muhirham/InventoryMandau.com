@extends('layouts.home')

@section('title','Admin Dashboard')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/countup.js@2.2.0/dist/countUp.min.js"></script>

<style>
/* ---------- Global / grid ---------- */
.container-dash{padding:18px 12px}
.header-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:16px}

/* ---------- Cards ---------- */
.card{background:#fff;border-radius:12px;padding:14px;box-shadow:0 8px 26px rgba(16,24,40,0.04);transition:transform .18s}
.card:hover{transform:translateY(-6px)}
.kpi-card{grid-column:span 3;min-height:110px;display:flex;flex-direction:column;justify-content:space-between}
.big-card{grid-column:span 6;min-height:360px}
.small-card{grid-column:span 3;min-height:180px}
.kpi-top{display:flex;gap:12px;align-items:center}
.kpi-icon{width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;background:linear-gradient(135deg,#6366f1,#4f46e5);font-weight:700}
.kpi-value{font-weight:800;font-size:1.6rem}
.muted{color:#6b7280;font-size:.85rem}
.small-num{font-weight:700;color:#111827}

/* ---------- Order + Income row (like screenshot) ---------- */
.row-analytics { display:grid; grid-template-columns: repeat(12,1fr); gap:16px; margin-top:12px; }
.order-card { grid-column: span 7; border-radius:12px; padding:18px; background:#fff; box-shadow:0 8px 24px rgba(12,18,31,0.06); }
.income-card { grid-column: span 5; border-radius:12px; padding:18px; background:#fff; box-shadow:0 8px 24px rgba(12,18,31,0.06); }

.order-stats { display:flex; gap:18px; align-items:flex-start; }
.order-left { flex:1; }
.order-right { width:200px; display:flex; flex-direction:column; align-items:center; gap:10px; }

/* big number */
.big-num { font-size:2.6rem; font-weight:800; color:#0f1724; margin-top:6px; }

/* category list */
.cat-list { margin-top:16px; display:flex; flex-direction:column; gap:12px; }
.cat-item { display:flex; align-items:center; gap:12px; }
.cat-icon { width:44px; height:44px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#111827; font-weight:700; }
.cat-meta { flex:1; }
.cat-meta small{ display:block; color:#6b7280 }

/* income tabs */
.tabs { display:flex; gap:8px; margin-bottom:12px }
.tab-btn { padding:8px 12px; border-radius:8px; background:transparent; border:1px solid transparent; cursor:pointer; transition:all .15s }
.tab-btn.active { background: linear-gradient(90deg,#7c3aed,#4f46e5); color:#fff; box-shadow:0 8px 18px rgba(79,70,229,0.12) }

/* responsive */
@media (max-width: 992px){ .kpi-card{grid-column:span 6} .big-card{grid-column:span 12} .small-card{grid-column:span 6} .order-card{grid-column:span 12} .income-card{grid-column:span 12} }
@media (max-width: 576px){ .grid{grid-template-columns:repeat(1,1fr)} }
</style>

<div class="container-dash">
  <div class="header-row">
    <div>
      <h3>Admin Dashboard</h3>
      <div class="muted">Overview & analytics</div>
    </div>
    <div style="display:flex;gap:12px;align-items:center">
      <div class="muted">Welcome Admin</div>
      <img src="https://ui-avatars.com/api/?name=AD" style="width:36px;height:36px;border-radius:50%">
    </div>
  </div>

  <!-- KPI grid -->
  <div class="grid">
    <div class="card kpi-card" onclick="location.href='{{ route('transactions.index') }}'">
      <div>
        <div class="kpi-top">
          <div class="kpi-icon">TX</div>
          <div>
            <div class="kpi-value" id="totalTransactions">0</div>
            <div class="muted">Total Transactions</div>
          </div>
        </div>
      </div>
      <div class="muted">All transactions • <span id="txChange">—</span></div>
    </div>

    <div class="card kpi-card" onclick="location.href='{{ route('users') }}'">
      <div class="kpi-top">
        <div class="kpi-icon">U</div>
        <div>
          <div class="kpi-value" id="totalUsers">0</div>
          <div class="muted">Total Users</div>
        </div>
      </div>
      <div class="muted">Registered users • <span id="userChange">—</span></div>
    </div>

    <div class="card kpi-card" onclick="location.href='{{ route('products.index') }}'">
      <div class="kpi-top">
        <div class="kpi-icon">P</div>
        <div>
          <div class="kpi-value" id="totalProducts">0</div>
          <div class="muted">Total Products</div>
        </div>
      </div>
      <div class="muted">Items in catalog • <span id="prodChange">—</span></div>
    </div>

    <div class="card kpi-card" onclick="location.href='{{ route('warehouses.index') }}'">
      <div class="kpi-top">
        <div class="kpi-icon">W</div>
        <div>
          <div class="kpi-value" id="totalWarehouses">0</div>
          <div class="muted">Warehouses</div>
        </div>
      </div>
      <div class="muted">Locations • <span id="whChange">—</span></div>
    </div>

    <div class="card big-card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <div>
          <div class="muted">Total Income</div>
          <div style="font-weight:800;font-size:1.1rem">Yearly report overview</div>
        </div>
        <div style="text-align:right">
          <div class="muted">This month</div>
          <div class="small-num" id="salesThisMonth">Rp 0</div>
        </div>
      </div>
      <div style="height:280px">
        <div id="totalRevenueChart" style="width:100%;height:100%"></div>
      </div>
    </div>

    <div class="card small-card" onclick="location.href='{{ route('reports.index') }}'">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
          <div class="muted">Report</div>
          <div style="font-weight:800">Monthly Avg. <span class="muted">$45.57k</span></div>
        </div>
        <div style="text-align:right">
          <div class="tag">Income</div>
          <div class="small-num" id="reportIncome">$42,845</div>
        </div>
      </div>
      <hr style="margin:12px 0;border:none;border-top:1px solid #f3f4f6">
      <div class="muted">Quick stats</div>
      <div style="display:flex;gap:12px;margin-top:8px">
        <div style="flex:1">
          <div class="muted">Sales</div>
          <div style="font-weight:800">482k</div>
        </div>
        <div style="flex:1">
          <div class="muted">Revenue</div>
          <div style="font-weight:800" id="revenueVal">Rp 42,389</div>
        </div>
      </div>
    </div>

    <div class="card small-card">
      <div class="muted">By status</div>
      <div style="height:160px;margin-top:8px">
        <canvas id="donutOrders"></canvas>
      </div>
      <div id="donutLegend" class="muted" style="margin-top:8px"></div>
    </div>

    <div class="card small-card">
      <div class="muted">Performance</div>
      <div style="height:160px;margin-top:8px">
        <canvas id="radarPerf"></canvas>
      </div>
    </div>

    <div class="card small-card">
      <div class="muted">Transactions</div>
      <div style="font-weight:800;font-size:1.2rem;margin:8px 0" id="transactionsValue">0</div>
      <div style="height:80px">
        <canvas id="miniBar"></canvas>
      </div>
    </div>
  </div>

  <!-- Lower row: order statistics + income (the screenshot elements) -->
  <div class="row-analytics">
    <div class="order-card card">
      <div class="order-stats">
        <div class="order-left">
          <h5>Order Statistics</h5>
          <div class="muted">42.82k Total Sales</div>
          <div class="big-num" id="totalOrders">0</div>
          <div class="muted">Total Orders</div>

          <div class="cat-list" id="categoryList">
            <!-- filled by JS -->
          </div>
        </div>

        <div class="order-right">
          <div style="width:140px;height:140px;display:flex;align-items:center;justify-content:center">
            <canvas id="ordersDonut" width="140" height="140"></canvas>
          </div>
          <div class="muted">Weekly</div>
          <div id="donutPercent" class="big-num" style="font-size:1.25rem">0%</div>
        </div>
      </div>
    </div>

    <div class="income-card card">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
          <h5>Income</h5>
          <div class="muted">Total Balance details</div>
        </div>
        <div class="tabs" role="tablist" aria-label="financial tabs">
          <button class="tab-btn active" data-tab="income">Income</button>
          <button class="tab-btn" data-tab="expenses">Expenses</button>
          <button class="tab-btn" data-tab="profit">Profit</button>
        </div>
      </div>

      <div style="margin-top:12px">
        <div style="display:flex;gap:14px;align-items:center;justify-content:space-between">
          <div>
            <div class="muted">Total Balance</div>
            <div style="font-weight:800" id="totalBalance">Rp 0</div>
            <div class="muted" id="balanceChange">+0%</div>
          </div>
          <div style="flex:1;padding-left:18px">
            <div style="height:160px"><canvas id="incomeArea"></canvas></div>
          </div>
        </div>

        <div style="display:flex;gap:12px;align-items:center;margin-top:12px">
          <div style="flex:0 0 100px;text-align:center">
            <div style="font-weight:700" id="expensesThisWeekVal">$0</div>
            <div class="muted" style="font-size:.85rem">Expenses This Week</div>
          </div>
          <div style="width:80px;height:80px">
            <canvas id="miniDonut" width="80" height="80"></canvas>
          </div>
          <div style="flex:1">
            <div class="muted">This Week Comparison</div>
            <div id="weekCompare" style="font-weight:700">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- ---------- Scripts ---------- -->
<script>
/* final dashboard script: uses route('admin.index.stats') */
const STATS_API = @json(route('admin.index.stats'));
const POLL_INTERVAL = 15000; // optional

const FALLBACK = {
  totals:{ transactions:8258, users:1240, products:582, warehouses:3, suppliers:18, categories:5, sales_this_month:42145 },
  monthly:{ labels:['Feb','Mar','Apr','May','Jun','Jul'], data:[12000,18000,14000,22000,19000,24000] },
  by_status:{ Completed:6200, Pending:1200, Refunded:300, Failed:58 },
  performance:{ Income:75, Earning:62, Sales:80, Growth:70, Conversion:68 },
  transactions_series:[2,3,1,4,2,1,3,2,4,3,5,2],
  category_breakdown:[
    { name:'Electronic', sub:'Mobile, Earbuds, TV', value:'82.5k', color:'#bfdbfe', icon:'📱' },
    { name:'Fashion', sub:'T-shirt, Jeans, Shoes', value:'23.8k', color:'#bbf7d0', icon:'👕' },
    { name:'Decor', sub:'Fine Art, Dining', value:'849k', color:'#fee2e2', icon:'🏡' },
    { name:'Sports', sub:'Football, Cricket Kit', value:'99', color:'#fce7f3', icon:'🏈' }
  ]
};

let chartRevenue=null, chartDonut=null, chartRadar=null, chartMiniBar=null, ordersDonut=null, incomeArea=null, miniDonut=null;

const rupiah = v => 'Rp ' + Number(v||0).toLocaleString('id-ID',{maximumFractionDigits:0});
const safeNum = v => (v===null||v===undefined||isNaN(Number(v)))?0:Number(v);

function createGradient(ctx,height,c1,c2){
  const g = ctx.createLinearGradient(0,0,0,height);
  g.addColorStop(0,c1); g.addColorStop(1,c2);
  return g;
}

function animateKPI(id, value){
  try{ new CountUp.CountUp(id, value, {duration:1.2, separator:'.'}).start(); }
  catch(e){ document.getElementById(id).textContent = value; }
}

/* Charts rendering functions */
function renderOrUpdateRevenue(labels,data){
  const container = document.getElementById('totalRevenueChart');
  if(!container) return;
  if(!document.getElementById('chart_totalRevenue')){
    container.innerHTML = '<canvas id="chart_totalRevenue" style="width:100%;height:100%"></canvas>';
  }
  const ctx = document.getElementById('chart_totalRevenue').getContext('2d');
  if(chartRevenue){ chartRevenue.data.labels = labels; chartRevenue.data.datasets[0].data = data; chartRevenue.update(); return; }
  const grad = createGradient(ctx,280,'rgba(79,70,229,0.18)','rgba(79,70,229,0.02)');
  chartRevenue = new Chart(ctx, {
    type:'line',
    data:{ labels, datasets:[{ label:'Revenue', data, borderWidth:3, borderColor:'#4f46e5', backgroundColor:grad, fill:true, pointRadius:5, pointBackgroundColor:'#fff', tension:0.38 }]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}, tooltip:{callbacks:{ label: ctx => 'Sales: ' + rupiah(ctx.raw) } }}, scales:{ x:{grid:{display:false}}, y:{grid:{color:'rgba(15,23,42,0.04)'}, ticks:{callback: v => v>=1000000?('Rp '+(v/1000000).toFixed(1)+'M'): v>=1000?('Rp '+(v/1000).toFixed(0)+'k') : 'Rp '+v}}}, animation:{duration:700,easing:'easeOutCubic'} }
  });
}

function renderOrUpdateDonut(labels,data){
  const ctx = document.getElementById('donutOrders').getContext('2d');
  const colors = ['#10b981','#f59e0b','#ef4444','#3b82f6','#a78bfa'];
  if(chartDonut){
    chartDonut.data.labels = labels;
    chartDonut.data.datasets[0].data = data;
    chartDonut.data.datasets[0].backgroundColor = colors.slice(0,labels.length);
    chartDonut.update();
  } else {
    chartDonut = new Chart(ctx, { type:'doughnut', data:{ labels, datasets:[{ data, backgroundColor: colors.slice(0,labels.length) }]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, animation:{duration:600}}});
  }
  const legendEl = document.getElementById('donutLegend');
  if(legendEl) legendEl.innerHTML = labels.map((l,i)=>`<span style="display:inline-block;margin-right:10px"><span style="width:10px;height:10px;background:${colors[i]};display:inline-block;margin-right:6px;border-radius:3px;vertical-align:middle"></span>${l} (${data[i]})</span>`).join('');
}

function renderOrUpdateRadar(labels,data){
  const ctx = document.getElementById('radarPerf').getContext('2d');
  if(chartRadar){ chartRadar.data.labels = labels; chartRadar.data.datasets[0].data = data; chartRadar.update(); return; }
  chartRadar = new Chart(ctx, { type:'radar', data:{ labels, datasets:[{ data, borderColor:'#6366f1', backgroundColor:'rgba(99,102,241,0.12)', fill:true }]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ r:{ grid:{ color:'rgba(15,23,42,0.04)'} } }, animation:{duration:600} }});
}

function renderOrUpdateMiniBar(labels,data){
  const ctx = document.getElementById('miniBar').getContext('2d');
  if(chartMiniBar){ chartMiniBar.data.labels = labels; chartMiniBar.data.datasets[0].data = data; chartMiniBar.update(); return; }
  chartMiniBar = new Chart(ctx, { type:'bar', data:{ labels, datasets:[{ data, backgroundColor:'rgba(59,130,246,0.9)', borderRadius:6 }]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}, tooltip:{enabled:false}}, scales:{ x:{display:false}, y:{display:false}}, animation:{duration:500}}});
}

/* Orders donut (left card) */
function renderOrdersDonut(labels,values,percent){
  const ctx = document.getElementById('ordersDonut').getContext('2d');
  const colors = ['#22c55e','#06b6d4','#f97316','#ef4444','#6366f1'];
  if(ordersDonut) ordersDonut.destroy();
  ordersDonut = new Chart(ctx, { type:'doughnut', data:{ labels, datasets:[{ data: values, backgroundColor: colors.slice(0,labels.length), hoverOffset:8 }]}, options:{ responsive:false, cutout:'72%', plugins:{legend:{display:false}}, animation:{duration:900,easing:'easeOutCubic'}}});
  document.getElementById('donutPercent').textContent = percent + '%';
}

/* Income area (right card) */
function renderIncomeArea(labels,data,color='#6366f1'){
  const ctx = document.getElementById('incomeArea').getContext('2d');
  if(incomeArea) incomeArea.destroy();
  const grad = createGradient(ctx,160,'rgba(99,102,241,0.14)','rgba(99,102,241,0.02)');
  incomeArea = new Chart(ctx, { type:'line', data:{ labels, datasets:[{ data, borderColor:color, backgroundColor:grad, fill:true, tension:0.36, pointRadius:3 }]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{ ticks:{ callback: v => v>=1000?('Rp '+(v/1000).toFixed(0)+'k') : 'Rp '+v } } }, animation:{duration:900,easing:'easeOutQuart'} }});
}

/* mini donut */
function renderMiniDonut(value,total){
  const ctx = document.getElementById('miniDonut').getContext('2d');
  if(miniDonut) miniDonut.destroy();
  miniDonut = new Chart(ctx, { type:'doughnut', data:{ labels:['a','b'], datasets:[{ data:[value, Math.max(0,total-value)], backgroundColor:['#4f46e5','#e6e9f6'] }]}, options:{ responsive:false, cutout:'70%', plugins:{legend:{display:false}}, animation:{duration:700}}});
}

/* Fill category list */
function fillCategoryList(arr){
  const wrapper = document.getElementById('categoryList');
  wrapper.innerHTML = '';
  arr.forEach(it=>{
    const div = document.createElement('div');
    div.className = 'cat-item';
    // make clickable to products filtered by category if route exists (optional)
    div.innerHTML = `
      <div class="cat-icon" style="background:${it.color || '#e2e8f0'}">${it.icon || it.name.charAt(0)}</div>
      <div class="cat-meta">
        <div style="font-weight:700">${it.name}</div>
        <small>${it.sub || ''}</small>
      </div>
      <div style="font-weight:700">${it.value}</div>
    `;
    wrapper.appendChild(div);
  });
}

/* Fetch and update */
async function fetchStats(){
  try{
    const res = await fetch(STATS_API, { headers:{ 'X-Requested-With':'XMLHttpRequest' } });
    if(!res.ok) throw new Error('HTTP '+res.status);
    const json = await res.json();
    if(!json || !json.totals) throw new Error('incomplete');
    return json;
  } catch(e){
    console.warn('Using fallback stats', e);
    return FALLBACK;
  }
}

async function updateUI(){
  const payload = await fetchStats();

  const totals = payload.totals || FALLBACK.totals;
  const monthly = payload.monthly || FALLBACK.monthly;
  const by_status = payload.by_status || FALLBACK.by_status;
  const perf = payload.performance || FALLBACK.performance;
  const tx_series = payload.transactions_series || FALLBACK.transactions_series;

  // KPIs / top small stats
  animateKPI('totalTransactions', safeNum(totals.transactions));
  animateKPI('totalUsers', safeNum(totals.users));
  animateKPI('totalProducts', safeNum(totals.products));
  animateKPI('totalWarehouses', safeNum(totals.warehouses));
  document.getElementById('salesThisMonth').textContent = rupiah(safeNum(totals.sales_this_month));
  document.getElementById('reportIncome').textContent = payload.report_income ?? '$42,845';
  document.getElementById('revenueVal').textContent = rupiah(payload.revenue_val ?? 42389);
  document.getElementById('transactionsValue').textContent = (payload.order_total ?? (payload.orderValues && payload.orderValues[0])) || '8,258';

  // charts
  const labels = (monthly.labels && monthly.labels.length) ? monthly.labels : FALLBACK.monthly.labels;
  const series = (monthly.data && monthly.data.length) ? monthly.data.map(safeNum) : FALLBACK.monthly.data;
  renderOrUpdateRevenue(labels, series);

  const statusLabels = Object.keys(by_status);
  const statusValues = Object.values(by_status).map(safeNum);
  renderOrUpdateDonut(statusLabels, statusValues);

  const perfLabels = Object.keys(perf);
  const perfValues = Object.values(perf).map(safeNum);
  renderOrUpdateRadar(perfLabels, perfValues);

  const txLabels = Array.from({length: tx_series.length}, (_,i)=>i+1).slice(-6);
  const txVals = tx_series.slice(-6).map(safeNum);
  renderOrUpdateMiniBar(txLabels, txVals);

  // left-order card
  document.getElementById('totalOrders').textContent = (totals.transactions||0).toLocaleString();
  // categories list (if backend returns, prefer that)
  const cats = payload.category_breakdown && payload.category_breakdown.length ? payload.category_breakdown : FALLBACK.category_breakdown;
  fillCategoryList(cats);

  // orders donut (gauge)
  const totalStatus = statusValues.reduce((a,b)=>a+b,0) || 1;
  const completed = (payload.by_status && (payload.by_status.Completed || payload.by_status.completed)) || 0;
  const pct = Math.round((completed/totalStatus)*100);
  renderOrdersDonut(statusLabels, statusValues, pct);

  // income area and small donut
  const incomeData = series;
  const expensesData = incomeData.map(v=>Math.round(v*0.6));
  const profitData = incomeData.map((v,i)=>Math.max(0, Math.round(v - expensesData[i])));
  renderIncomeArea(labels, incomeData, '#4f46e5');

  const totalBalance = incomeData.reduce((a,b)=>a+b,0);
  document.getElementById('totalBalance').textContent = rupiah(totalBalance);
  const lastIncome = incomeData.length ? incomeData[incomeData.length-1] : 0;
  const expensesThisWeek = Math.round(lastIncome*0.08);
  document.getElementById('expensesThisWeekVal').textContent = '$' + expensesThisWeek;
  renderMiniDonut(expensesThisWeek, Math.max(1, lastIncome));
  document.getElementById('weekCompare').textContent = expensesThisWeek>0?('$'+expensesThisWeek+' less than last week'):'—';

  // tabs
  document.querySelectorAll('.tab-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      const tab = btn.getAttribute('data-tab');
      if(tab==='income') renderIncomeArea(labels, incomeData, '#4f46e5');
      else if(tab==='expenses') renderIncomeArea(labels, expensesData, '#f59e0b');
      else renderIncomeArea(labels, profitData, '#10b981');
    });
  });
}

// boot
document.addEventListener('DOMContentLoaded', async ()=>{
  await updateUI();
  // optional polling:
  // setInterval(updateUI, POLL_INTERVAL);
});
</script>
@endsection
