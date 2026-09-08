<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Der Mensch — Vocabulaire</title>
@include('partials._style')
</head>
<body>

<div class="masthead">
  <div class="kicker">Vocabulaire Allemand · I. Der Mensch</div>
  <h1>Personalien &amp; Familie</h1>
</div>

<div class="userbar">
  <span class="who">Session : <strong>{{ auth()->user()->username }}</strong></span>
  <span>
    <button type="button" class="linkish" id="btnReset">tout réinitialiser</button>
    &nbsp;·&nbsp;
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="linkish">changer d'utilisateur</button>
    </form>
  </span>
</div>

<div class="direction-row" id="directionRow">
  <span class="direction-label">Sens :</span>
  <div class="direction-pill active" id="dirDeFr">Allemand → Français</div>
  <div class="direction-pill" id="dirFrDe">Français → Allemand</div>
</div>

<div class="filter-row" id="filterRow">
  <div class="filter-pill active" data-filter="all">Tous</div>
  <div class="filter-pill" data-filter="known">Connus (<span id="knownCount">0</span>)</div>
  <div class="filter-pill" data-filter="review">À revoir (<span id="reviewCount">0</span>)</div>
</div>

<div class="status-panel">
  <div class="status-block">
    <div class="status-label known-label">Connus</div>
    <div class="status-tags" id="knownTags"><span class="empty-hint">—</span></div>
  </div>
  <div class="status-block">
    <div class="status-label review-label">À revoir</div>
    <div class="status-tags" id="reviewTags"><span class="empty-hint">—</span></div>
  </div>
</div>

<div class="progress-row">
  <span id="posLabel">1 / {{ count($deck) }}</span>
  <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
</div>

<div class="stage" id="stage">
  <div class="card" id="card">
    <div class="face front">
      <span class="face-tag" id="frontTag">Deutsch</span>
      <div class="word" id="frontWord"></div>
      <div class="hint">tap to reveal ↻</div>
    </div>
    <div class="face back">
      <span class="face-tag" id="backTag">Français</span>
      <div class="word" id="backWord"></div>
      <div class="example" id="example"></div>
    </div>
  </div>
</div>

<div class="controls">
  <button class="review" id="btnReview">à revoir</button>
  <button class="known" id="btnKnown">je sais</button>
</div>
<div class="nav-row">
  <button id="btnPrev">← précédent</button>
  <button id="btnShuffle">mélanger</button>
  <button id="btnNext">suivant →</button>
</div>

<div class="sync-note" id="syncNote"></div>

<details class="full-list" id="fullList">
  <summary>Liste complète du vocabulaire (Français → Deutsch)</summary>
  <table id="vocabTable">
    <thead>
      <tr><th>Français</th><th>Deutsch</th></tr>
    </thead>
    <tbody id="vocabTableBody"></tbody>
  </table>
</details>

<script>
const deck = @json($deck);
const savedStates = @json((object) $states);
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const statusUrlFor = (id) => '/cards/' + id + '/status';
const resetUrl = @json(route('cards.reset'));

let order = deck.map((_,i)=>i);
let pos = 0;
let flipped = false;
let knownSet = new Set();
let reviewSet = new Set();
let direction = "de-fr"; // "de-fr" = Allemand affiché d'abord, "fr-de" = Français affiché d'abord
let filterState = "all"; // "all" | "known" | "review"

// Restaure la session : les statuts viennent de la base, par id de carte.
deck.forEach((item, i)=>{
  const status = savedStates[item.id];
  if(status === 'known') knownSet.add(i);
  if(status === 'review') reviewSet.add(i);
});

const cardEl = document.getElementById('card');
const stage = document.getElementById('stage');
const frontTag = document.getElementById('frontTag');
const frontWord = document.getElementById('frontWord');
const backTag = document.getElementById('backTag');
const backWord = document.getElementById('backWord');
const example = document.getElementById('example');
const posLabel = document.getElementById('posLabel');
const progressFill = document.getElementById('progressFill');
const dirDeFr = document.getElementById('dirDeFr');
const dirFrDe = document.getElementById('dirFrDe');
const knownCount = document.getElementById('knownCount');
const reviewCount = document.getElementById('reviewCount');
const knownTags = document.getElementById('knownTags');
const reviewTags = document.getElementById('reviewTags');
const filterPills = document.querySelectorAll('.filter-pill');
const syncNote = document.getElementById('syncNote');

function note(msg, isError){
  syncNote.textContent = msg || '';
  syncNote.classList.toggle('error', !!isError);
}

// Persiste le statut d'une carte (status = 'known' | 'review' | null pour effacer).
async function persist(deckIndex, status){
  try{
    const res = await fetch(statusUrlFor(deck[deckIndex].id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({status: status})
    });
    if(!res.ok) throw new Error('HTTP ' + res.status);
    note('');
  }catch(e){
    note('Enregistrement impossible — vérifiez la connexion.', true);
  }
}

dirDeFr.onclick = ()=>{
  direction = "de-fr";
  dirDeFr.classList.add('active');
  dirFrDe.classList.remove('active');
  render();
};
dirFrDe.onclick = ()=>{
  direction = "fr-de";
  dirFrDe.classList.add('active');
  dirDeFr.classList.remove('active');
  render();
};

function listForFilter(f){
  if(f === "known") return Array.from(knownSet);
  if(f === "review") return Array.from(reviewSet);
  return deck.map((_,i)=>i);
}

function setFilter(f){
  filterState = f;
  filterPills.forEach(p=> p.classList.toggle('active', p.dataset.filter === f));
  order = listForFilter(f);
  pos = 0;
  render();
}

filterPills.forEach(p=>{
  p.onclick = ()=> setFilter(p.dataset.filter);
});

// Recompute the current order after knownSet/reviewSet change, keeping position sensible
function refreshOrderForFilter(){
  order = listForFilter(filterState);
  if(pos >= order.length) pos = Math.max(0, order.length - 1);
  render();
}

function renderStatusLists(){
  knownCount.textContent = knownSet.size;
  reviewCount.textContent = reviewSet.size;

  knownTags.innerHTML = '';
  if(knownSet.size === 0){
    knownTags.innerHTML = '<span class="empty-hint">—</span>';
  } else {
    knownSet.forEach(idx=>{
      const tag = document.createElement('span');
      tag.className = 'status-tag known-tag';
      tag.textContent = deck[idx].de;
      tag.title = 'Retirer de la liste';
      tag.onclick = ()=>{ knownSet.delete(idx); persist(idx, null); renderStatusLists(); refreshOrderForFilter(); };
      knownTags.appendChild(tag);
    });
  }

  reviewTags.innerHTML = '';
  if(reviewSet.size === 0){
    reviewTags.innerHTML = '<span class="empty-hint">—</span>';
  } else {
    reviewSet.forEach(idx=>{
      const tag = document.createElement('span');
      tag.className = 'status-tag review-tag';
      tag.textContent = deck[idx].de;
      tag.title = 'Retirer de la liste';
      tag.onclick = ()=>{ reviewSet.delete(idx); persist(idx, null); renderStatusLists(); refreshOrderForFilter(); };
      reviewTags.appendChild(tag);
    });
  }
}

function render(){
  if(order.length === 0){
    const emptyLabel = filterState === "known" ? "Connus" : (filterState === "review" ? "À revoir" : "Tous");
    frontTag.textContent = emptyLabel;
    frontWord.textContent = "Aucun mot dans cette liste.";
    backTag.textContent = "";
    backWord.textContent = "";
    example.textContent = "";
    flipped = false;
    cardEl.classList.remove('flipped');
    posLabel.textContent = "0 / 0";
    progressFill.style.width = "0%";
    return;
  }
  const item = deck[order[pos]];
  if(direction === "de-fr"){
    frontTag.textContent = "Deutsch";
    frontWord.textContent = item.de;
    backTag.textContent = "Français";
    backWord.textContent = item.fr;
  } else {
    frontTag.textContent = "Français";
    frontWord.textContent = item.fr;
    backTag.textContent = "Deutsch";
    backWord.textContent = item.de;
  }
  example.textContent = item.ex ? item.ex : '';
  flipped = false;
  cardEl.classList.remove('flipped');
  posLabel.textContent = (pos+1) + ' / ' + order.length;
  progressFill.style.width = ((pos)/(order.length-1||1)*100) + '%';
}

cardEl.addEventListener('click', ()=>{
  if(order.length === 0) return;
  flipped = !flipped;
  cardEl.classList.toggle('flipped', flipped);
});

function next(){
  if(pos < order.length - 1){ pos++; render(); }
}
function prev(){
  if(pos > 0){ pos--; render(); }
}
function shuffle(){
  for(let i=order.length-1;i>0;i--){
    const j = Math.floor(Math.random()*(i+1));
    [order[i],order[j]] = [order[j],order[i]];
  }
  pos = 0;
  render();
}


document.getElementById('btnNext').onclick = next;
document.getElementById('btnPrev').onclick = prev;
document.getElementById('btnShuffle').onclick = shuffle;
document.getElementById('btnKnown').onclick = ()=>{
  if(order.length === 0) return;
  const idx = order[pos];
  knownSet.add(idx);
  reviewSet.delete(idx);
  persist(idx, 'known');
  renderStatusLists();
  if(filterState === "all"){
    next();
  } else {
    refreshOrderForFilter();
  }
};
document.getElementById('btnReview').onclick = ()=>{
  if(order.length === 0) return;
  const idx = order[pos];
  reviewSet.add(idx);
  knownSet.delete(idx);
  persist(idx, 'review');
  renderStatusLists();
  if(filterState === "all"){
    next();
  } else {
    refreshOrderForFilter();
  }
};

document.getElementById('btnReset').onclick = async ()=>{
  if(!confirm('Effacer vos listes « connus » et « à revoir » ?')) return;
  try{
    const res = await fetch(resetUrl, {
      method: 'POST',
      headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken}
    });
    if(!res.ok) throw new Error('HTTP ' + res.status);
    knownSet.clear();
    reviewSet.clear();
    renderStatusLists();
    setFilter('all');
    note('');
  }catch(e){
    note('Réinitialisation impossible — vérifiez la connexion.', true);
  }
};

document.addEventListener('keydown', (e)=>{
  if(e.target.matches('input, textarea')) return;
  if(e.key === 'ArrowRight') next();
  if(e.key === 'ArrowLeft') prev();
  if(e.key === ' '){ e.preventDefault(); flipped=!flipped; cardEl.classList.toggle('flipped', flipped); }
});

render();
renderStatusLists();

// Build the full vocabulary list table (French -> Deutsch), single flat alphabetical list
const vocabTableBody = document.getElementById('vocabTableBody');
const allItems = deck.slice().sort((a,b)=> a.fr.localeCompare(b.fr, 'fr'));
allItems.forEach(item=>{
  const row = document.createElement('tr');
  const frCell = document.createElement('td');
  frCell.textContent = item.fr;
  const deCell = document.createElement('td');
  deCell.textContent = item.de;
  row.appendChild(frCell);
  row.appendChild(deCell);
  vocabTableBody.appendChild(row);
});
</script>

</body>
</html>
