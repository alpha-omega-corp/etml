<style>
  :root{
    --paper:#efe9dc;
    --ink:#2b2620;
    --rule:#c9bfa8;
    --accent:#8a3324;
    --accent-2:#3d5a45;
    --card:#faf6ec;
  }
  *{box-sizing:border-box;}
  body{
    margin:0;
    background:var(--paper);
    background-image:
      repeating-linear-gradient(0deg, rgba(0,0,0,0.015) 0px, rgba(0,0,0,0.015) 1px, transparent 1px, transparent 3px);
    font-family: 'Iowan Old Style','Palatino Linotype',Georgia, serif;
    color:var(--ink);
    display:flex;
    flex-direction:column;
    align-items:center;
    padding:28px 16px 48px;
    min-height:100vh;
  }
  .masthead{
    text-align:center;
    margin-bottom:6px;
  }
  .masthead h1{
    font-size:1.5rem;
    letter-spacing:0.03em;
    margin:0;
    font-weight:600;
  }
  .masthead .kicker{
    font-size:0.7rem;
    letter-spacing:0.18em;
    color:var(--accent);
    text-transform:uppercase;
    margin-bottom:4px;
  }
  .direction-row{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:6px;
  }
  .direction-label{
    font-size:0.72rem;
    color:#9b9382;
    letter-spacing:0.05em;
  }
  .direction-pill{
    font-size:0.72rem;
    padding:5px 12px;
    border:1px solid var(--rule);
    border-radius:12px;
    color:#6b6354;
    background:var(--card);
    cursor:pointer;
  }
  .direction-pill.active{
    background:var(--accent);
    color:var(--card);
    border-color:var(--accent);
  }
  .filter-row{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    justify-content:center;
    margin:14px 0 4px;
  }
  .filter-pill{
    font-size:0.76rem;
    padding:6px 14px;
    border:1px solid var(--rule);
    border-radius:14px;
    color:#6b6354;
    background:var(--card);
    cursor:pointer;
  }
  .filter-pill:hover{
    background:#f2ecdd;
  }
  .filter-pill.active{
    background:var(--ink);
    color:var(--paper);
    border-color:var(--ink);
  }
  .progress-row{
    display:flex;
    align-items:center;
    gap:10px;
    margin:14px 0 22px;
    font-size:0.8rem;
    color:#665f52;
  }
  .progress-bar{
    width:180px;
    height:4px;
    background:var(--rule);
    border-radius:2px;
    overflow:hidden;
  }
  .progress-fill{
    height:100%;
    background:var(--accent-2);
    width:0%;
    transition:width .25s ease;
  }
  .stage{
    perspective:1400px;
    width:min(92vw,420px);
    height:280px;
  }
  .card{
    position:relative;
    width:100%;
    height:100%;
    transform-style:preserve-3d;
    transition:transform .5s cubic-bezier(.4,.2,.2,1);
    cursor:pointer;
  }
  .card.flipped{ transform: rotateY(180deg); }
  .face{
    position:absolute;
    inset:0;
    border-radius:6px;
    background:var(--card);
    border:1px solid var(--rule);
    box-shadow: 0 1px 0 var(--rule), 0 8px 20px -12px rgba(43,38,32,0.35);
    backface-visibility:hidden;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:28px 26px;
    text-align:center;
  }
  .face::before{
    content:"";
    position:absolute;
    top:14px; left:14px; right:14px; bottom:14px;
    border:1px solid var(--rule);
    border-radius:3px;
    pointer-events:none;
    opacity:0.6;
  }
  .face-tag{
    position:absolute;
    top:22px; left:26px;
    font-size:0.62rem;
    letter-spacing:0.16em;
    text-transform:uppercase;
    color:#9b9382;
  }
  .word{
    font-size:1.55rem;
    line-height:1.3;
    font-weight:600;
  }
  .gender-de{ color:var(--accent); }
  .plural-info{
    margin-top:10px;
    font-size:0.82rem;
    color:#6b6354;
    font-style:italic;
  }
  .example{
    margin-top:14px;
    font-size:0.78rem;
    color:#54503f;
    line-height:1.45;
    max-width:300px;
  }
  .back .word{ color:var(--accent-2); }
  .face.back{ transform: rotateY(180deg); }
  .hint{
    margin-top:16px;
    font-size:0.72rem;
    color:#9b9382;
    letter-spacing:0.04em;
  }
  .controls{
    display:flex;
    gap:14px;
    margin-top:24px;
    align-items:center;
  }
  button{
    font-family:inherit;
    background:none;
    border:1px solid var(--ink);
    color:var(--ink);
    padding:10px 18px;
    font-size:0.85rem;
    border-radius:3px;
    cursor:pointer;
    letter-spacing:0.02em;
  }
  button:hover{ background:var(--ink); color:var(--paper); }
  button.primary{
    background:var(--accent-2);
    border-color:var(--accent-2);
    color:var(--card);
  }
  button.primary:hover{ background:#2e4433; border-color:#2e4433; }
  button.known{
    border-color:var(--accent-2);
    color:var(--accent-2);
  }
  button.known:hover{ background:var(--accent-2); color:var(--card); }
  button.review{
    border-color:var(--accent);
    color:var(--accent);
  }
  button.review:hover{ background:var(--accent); color:var(--card); }
  .nav-row{
    display:flex;
    gap:10px;
    margin-top:14px;
  }
  .status-panel{
    width:min(92vw,420px);
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-bottom:16px;
  }
  .status-block{
    background:var(--card);
    border:1px solid var(--rule);
    border-radius:4px;
    padding:8px 12px;
  }
  .status-label{
    font-size:0.68rem;
    letter-spacing:0.1em;
    text-transform:uppercase;
    margin-bottom:6px;
  }
  .known-label{ color:var(--accent-2); }
  .review-label{ color:var(--accent); }
  .status-tags{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
  }
  .empty-hint{
    font-size:0.78rem;
    color:#b5ac99;
    font-style:italic;
  }
  .status-tag{
    font-size:0.72rem;
    padding:3px 9px;
    border-radius:10px;
    cursor:pointer;
    border:1px solid transparent;
  }
  .status-tag.known-tag{
    background:#e7efe6;
    color:var(--accent-2);
    border-color:#c7dac2;
  }
  .status-tag.review-tag{
    background:#f3e6e0;
    color:var(--accent);
    border-color:#e3c4b8;
  }
  .status-tag:hover{
    opacity:0.7;
  }

  .full-list{
    width:min(92vw,520px);
    margin-top:30px;
  }
  .full-list summary{
    cursor:pointer;
    font-size:0.85rem;
    padding:10px 14px;
    background:var(--card);
    border:1px solid var(--rule);
    border-radius:4px;
    color:var(--ink);
    letter-spacing:0.02em;
  }
  .full-list summary:hover{
    background:#f2ecdd;
  }
  .full-list[open] summary{
    border-bottom-left-radius:0;
    border-bottom-right-radius:0;
  }
  table#vocabTable{
    width:100%;
    border-collapse:collapse;
    background:var(--card);
    border:1px solid var(--rule);
    border-top:none;
    font-size:0.82rem;
  }
  table#vocabTable thead th{
    text-align:left;
    padding:8px 12px;
    font-size:0.68rem;
    letter-spacing:0.1em;
    text-transform:uppercase;
    color:#9b9382;
    border-bottom:1px solid var(--rule);
    position:sticky;
    top:0;
    background:var(--card);
  }
  table#vocabTable td{
    padding:7px 12px;
    border-bottom:1px solid #e6ddc8;
    vertical-align:top;
  }
  table#vocabTable td:first-child{
    color:var(--accent-2);
    width:45%;
  }

  /* --- ajouts : barre de session --- */
  .userbar{
    width:min(92vw,520px);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    font-size:0.72rem;
    color:#9b9382;
    letter-spacing:0.05em;
    margin-bottom:14px;
  }
  .userbar .who{
    text-transform:uppercase;
  }
  .userbar .who strong{
    color:var(--ink);
    font-weight:600;
    letter-spacing:0.02em;
    text-transform:none;
    font-size:0.82rem;
  }
  .userbar form{ margin:0; display:inline; }
  .linkish{
    background:none;
    border:none;
    border-bottom:1px solid var(--rule);
    padding:0 0 1px;
    font-size:0.72rem;
    letter-spacing:0.05em;
    color:#6b6354;
    border-radius:0;
    cursor:pointer;
  }
  .linkish:hover{
    background:none;
    color:var(--accent);
    border-bottom-color:var(--accent);
  }
  .sync-note{
    font-size:0.68rem;
    color:#b5ac99;
    font-style:italic;
    min-height:1em;
    margin-top:10px;
  }
  .sync-note.error{ color:var(--accent); font-style:normal; }

  /* --- ajouts : page de connexion --- */
  .login-box{
    background:var(--card);
    border:1px solid var(--rule);
    border-radius:6px;
    box-shadow: 0 1px 0 var(--rule), 0 8px 20px -12px rgba(43,38,32,0.35);
    padding:30px 28px;
    width:min(92vw,360px);
    margin-top:26px;
    text-align:center;
  }
  .login-box label{
    display:block;
    font-size:0.68rem;
    letter-spacing:0.16em;
    text-transform:uppercase;
    color:#9b9382;
    margin-bottom:10px;
  }
  .login-box input{
    font-family:inherit;
    font-size:1.05rem;
    color:var(--ink);
    width:100%;
    padding:9px 10px;
    background:transparent;
    border:none;
    border-bottom:1px solid var(--rule);
    text-align:center;
    outline:none;
  }
  .login-box input:focus{ border-bottom-color:var(--accent-2); }
  .login-box button{ margin-top:22px; width:100%; }
  .login-note{
    margin-top:16px;
    font-size:0.72rem;
    color:#9b9382;
    font-style:italic;
    line-height:1.5;
  }
  .error-note{
    margin-top:14px;
    font-size:0.75rem;
    color:var(--accent);
  }
</style>
