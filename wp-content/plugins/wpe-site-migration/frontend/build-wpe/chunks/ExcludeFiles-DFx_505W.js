import { j as a, Q as S, R as m, a3 as g, _ as x, a2 as T } from "./indexWPE-Bsx8TyLp.js";
const B = (e) => {
  const w = (c) => {
    e.excludesUpdater(c.target.value, e.type);
  }, o = `exclude-files-${e.type}`, f = ({ type: c }) => {
    const { current_migration: b, local_site: j, remote_site: y } = T(
      (l) => l.migrations
    ), v = () => {
      var l, u, p, d, h, _;
      const { intent: E } = b, i = E === "pull", n = i ? y : j, {
        themes_path: R,
        plugins_path: F,
        muplugins_path: P,
        content_dir: k,
        root_path: C
      } = n.site_details, t = i ? n.path : n.this_path, s = (r, I) => r === void 0 ? null : r.replace(I, "").replace(/^\/|\/$/g, "");
      switch (c) {
        case "themes":
          return (l = s(R, t)) != null ? l : "wp-content/themes";
        case "plugins":
          return (u = s(F, t)) != null ? u : "wp-content/plugins";
        case "muplugins":
          return (p = s(P, t)) != null ? p : "wp-content/mu-plugins";
        case "media":
          const r = i ? n.wp_upload_dir : n.this_wp_upload_dir;
          return (d = s(r, t)) != null ? d : "wp-content/uploads";
        case "others":
          return (h = s(k, t)) != null ? h : "wp-content";
        case "root":
          return (_ = s(C, t)) != null ? _ : "/";
        default:
          return "";
      }
    };
    return /* @__PURE__ */ a.jsx("p", { children: m(
      g(
        x(
          'Use <a href="%s" target="_blank" rel="noopener noreferrer">gitignore patterns</a> to exclude files relative to <code>%s</code>',
          "wp-migrate-db"
        ),
        "https://deliciousbrains.com/wp-migrate-db-pro/doc/ignored-files/",
        v()
      )
    ) });
  };
  return /* @__PURE__ */ a.jsxs(S.Fragment, { children: [
    /* @__PURE__ */ a.jsx("h4", { className: "exclude-files-title", id: o, children: m(
      g(
        x(
          'Excluded Files<span class="screen-reader-text"> %s</span>',
          "wp-migrate-db"
        ),
        e.type
      )
    ) }),
    /* @__PURE__ */ a.jsx(f, { type: e.type }),
    /* @__PURE__ */ a.jsx(
      "textarea",
      {
        onChange: w,
        value: e.excludes || "",
        spellCheck: "false",
        "aria-labelledby": o
      }
    )
  ] });
};
export {
  B as E
};
//# sourceMappingURL=ExcludeFiles-DFx_505W.js.map
