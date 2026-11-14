@once
<style>
    .mg-hero { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #0f766e 100%); border-radius: 20px; color: #fff; display: grid; gap: 20px; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); padding: 24px 26px; box-shadow: 0 10px 30px -12px rgba(49, 46, 129, .55); }
    .mg-hero-stats { align-content: center; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14); border-radius: 16px; display: grid; gap: 16px; grid-template-columns: 1fr 1fr; padding: 16px 18px; }
    .mg-eyebrow { color: #5eead4; font-size: 11px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    .mg-section-title { align-items: center; color: var(--eo-text); display: flex; font-size: 14px; font-weight: 800; gap: 8px; }
    .mg-kpi-grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); }
    .mg-kpi { background: var(--eo-card); border: 1px solid var(--eo-border); border-radius: 16px; display: block; padding: 16px 18px; text-decoration: none; transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; }
    .mg-kpi:hover { border-color: rgba(99, 102, 241, .55); box-shadow: 0 8px 24px -14px rgba(49, 46, 129, .45); transform: translateY(-2px); }
    .mg-kpi-label { color: var(--eo-muted); font-size: 12.5px; font-weight: 700; margin-top: 12px; }
    .mg-kpi-value { color: var(--eo-text); font-size: 28px; font-weight: 800; letter-spacing: -.01em; line-height: 1.15; margin-top: 2px; }
    .mg-kpi-hint { color: var(--eo-muted); font-size: 12px; line-height: 1.45; margin-top: 8px; }
    .mg-icon { align-items: center; border-radius: 12px; display: inline-flex; height: 38px; justify-content: center; width: 38px; }
    .mg-icon.success { background: rgba(22, 163, 74, .12); color: var(--eo-success); }
    .mg-icon.info { background: rgba(99, 102, 241, .12); color: rgb(79, 70, 229); }
    .mg-icon.warning { background: rgba(217, 119, 6, .12); color: var(--eo-warning); }
    .dark .mg-icon.info { color: rgb(165, 180, 252); }
    .mg-pipeline { display: grid; gap: 10px; grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .mg-stage { background: var(--eo-soft); border: 1px solid var(--eo-border); border-radius: 14px; padding: 12px 14px; position: relative; }
    .mg-stage-num { align-items: center; border-radius: 999px; color: #fff; display: inline-flex; font-size: 12px; font-weight: 800; height: 24px; justify-content: center; width: 24px; }
    .mg-dist { border-radius: 999px; display: flex; height: 14px; overflow: hidden; background: var(--eo-track); }
    @media (max-width: 1100px) { .mg-hero { grid-template-columns: 1fr; } .mg-pipeline { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 640px) { .mg-pipeline { grid-template-columns: repeat(2, minmax(0, 1fr)); } .mg-hero { padding: 20px; } }
</style>
@endonce
