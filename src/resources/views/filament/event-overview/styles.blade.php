<style>
    .eo { --eo-border: rgba(148, 163, 184, .35); --eo-muted: rgb(100, 116, 139); --eo-text: rgb(17, 24, 39); --eo-card: #fff; --eo-soft: rgb(248, 250, 252); --eo-track: rgba(148, 163, 184, .22);
          --eo-primary: rgb(37, 99, 235); --eo-success: rgb(22, 163, 74); --eo-warning: rgb(217, 119, 6); --eo-danger: rgb(220, 38, 38); --eo-info: rgb(37, 99, 235);
          display: grid; gap: 16px; }
    .dark .eo { --eo-border: rgba(255, 255, 255, .1); --eo-muted: rgb(148, 163, 184); --eo-text: rgb(241, 245, 249); --eo-card: rgb(24, 24, 27); --eo-soft: rgba(255, 255, 255, .04); }
    .eo-card { background: var(--eo-card); border: 1px solid var(--eo-border); border-radius: 16px; }
    .eo-pad { padding: 18px 20px; }
    .eo-row { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; }
    .eo-between { justify-content: space-between; }
    .eo-title { color: var(--eo-text); font-size: 20px; font-weight: 800; line-height: 1.3; margin: 0; }
    .eo-h3 { color: var(--eo-text); font-size: 15px; font-weight: 800; margin: 0 0 12px; }
    .eo-muted { color: var(--eo-muted); font-size: 13px; line-height: 1.55; margin: 0; }
    .eo-meta { align-items: center; color: var(--eo-muted); display: inline-flex; font-size: 13px; gap: 6px; }
    .eo-meta svg { height: 16px; width: 16px; flex: 0 0 16px; }
    .eo-pill { border-radius: 999px; display: inline-flex; font-size: 12px; font-weight: 800; gap: 6px; padding: 4px 10px; white-space: nowrap; }
    .eo-pill.success { background: rgba(22, 163, 74, .12); color: var(--eo-success); }
    .eo-pill.warning { background: rgba(217, 119, 6, .12); color: var(--eo-warning); }
    .eo-pill.danger { background: rgba(220, 38, 38, .12); color: var(--eo-danger); }
    .eo-pill.info { background: rgba(37, 99, 235, .1); color: var(--eo-info); }
    .eo-pill.gray { background: var(--eo-soft); color: var(--eo-muted); border: 1px solid var(--eo-border); }
    .eo-count { background: var(--eo-text); border-radius: 10px; color: var(--eo-card); font-size: 13px; font-weight: 800; padding: 6px 10px; }
    .eo-steps { display: grid; gap: 8px; grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .eo-step { border-top: 4px solid var(--eo-track); padding-top: 10px; }
    .eo-step.done { border-color: var(--eo-success); }
    .eo-step.current { border-color: var(--eo-primary); }
    .eo-step-title { align-items: center; color: var(--eo-text); display: flex; font-size: 13px; font-weight: 800; gap: 6px; }
    .eo-step.todo .eo-step-title { color: var(--eo-muted); }
    .eo-step-title svg { height: 16px; width: 16px; flex: 0 0 16px; }
    .eo-step.done .eo-step-title svg { color: var(--eo-success); }
    .eo-step.current .eo-step-title svg { color: var(--eo-primary); }
    .eo-step-hint { color: var(--eo-muted); font-size: 12px; margin-top: 2px; }
    .eo-stats { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
    .eo-stat { background: var(--eo-soft); border: 1px solid var(--eo-border); border-radius: 12px; padding: 12px 14px; }
    .eo-stat-label { color: var(--eo-muted); font-size: 12px; font-weight: 700; }
    .eo-stat-value { color: var(--eo-text); font-size: 24px; font-weight: 800; line-height: 1.2; margin-top: 2px; }
    .eo-stat-value small { color: var(--eo-muted); font-size: 14px; font-weight: 700; }
    .eo-bar { background: var(--eo-track); border-radius: 999px; height: 6px; margin-top: 8px; overflow: hidden; }
    .eo-bar span { background: var(--eo-primary); border-radius: inherit; display: block; height: 100%; }
    .eo-btn { align-items: center; border: 1px solid var(--eo-border); border-radius: 10px; color: var(--eo-text); cursor: pointer; display: inline-flex; font-size: 13px; font-weight: 700; gap: 6px; padding: 8px 12px; text-decoration: none; background: var(--eo-card); }
    .eo-btn:hover { border-color: var(--eo-primary); color: var(--eo-primary); }
    .eo-btn svg { height: 16px; width: 16px; }
    .eo-btn.primary { background: var(--eo-primary); border-color: var(--eo-primary); color: #fff; }
    .eo-btn.primary:hover { color: #fff; opacity: .92; }
    .eo-next { border-left: 4px solid var(--eo-info); }
    .eo-next.warning { border-left-color: var(--eo-warning); }
    .eo-next.success { border-left-color: var(--eo-success); }
    .eo-next ul { color: var(--eo-text); font-size: 13px; line-height: 1.7; margin: 8px 0 0; padding-left: 18px; list-style: disc; }
    .eo-link-box { align-items: center; background: var(--eo-soft); border: 1px dashed var(--eo-border); border-radius: 10px; display: flex; gap: 8px; padding: 8px 10px; }
    .eo-link-box code { color: var(--eo-text); flex: 1; font-size: 12.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    @media (max-width: 900px) { .eo-steps { grid-template-columns: 1fr 1fr; } }
</style>
