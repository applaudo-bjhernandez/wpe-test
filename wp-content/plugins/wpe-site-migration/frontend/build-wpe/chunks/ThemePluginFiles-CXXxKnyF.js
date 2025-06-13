import { a2 as ie, ad as re, j as i, _ as r, R as y, ai as ae, G as o, ab as N, aj as le, a6 as oe, a3 as w, ak as ce, a8 as de, al as L, D as ue, am as pe, an as Y, ao as me, ac as he, a7 as ge, ap as U, aq as fe, W as _e, y as C, a4 as we, a5 as be, ar as xe, as as Pe, ag as ve, ah as ye, at as Ee, au as Oe } from "./indexWPE-Bsx8TyLp.js";
import { E as je } from "./ExcludeFiles-DFx_505W.js";
const Se = ({
  panelsOpen: e,
  items: s,
  selected: t,
  selectedOption: a,
  title: c,
  type: l,
  summary: d
}) => {
  const { enabled: m } = ie(
    (_) => _.theme_plugin_files[l] || {}
  );
  if (re(e, l) || !m)
    return null;
  if (["selected", "except"].includes(a) && t.length === 0)
    return /* @__PURE__ */ i.jsx("span", { className: "empty-warning", children: r("Empty selection", "wp-migrate-db") });
  const n = {
    muplugin_files: r("Selected mu-plugin files", "wp-migrate-db"),
    other_files: r("Selected other files", "wp-migrate-db"),
    root_files: r("Selected root files", "wp-migrate-db"),
    core_files: r("Export all Core files", "wp-migrate-db")
  }[l] || d;
  return /* @__PURE__ */ i.jsxs(i.Fragment, { children: [
    /* @__PURE__ */ i.jsx("div", { children: y(n) }),
    /* @__PURE__ */ i.jsx(
      ae,
      {
        selectedItems: t,
        totalItems: s,
        stageName: c,
        option: a
      }
    )
  ] });
}, Ce = ({ children: e }) => /* @__PURE__ */ i.jsx("ul", { className: "icon-list", children: e }), $e = (e) => /* @__PURE__ */ o.createElement("svg", { width: 16, height: 16, viewBox: "0 0 16 16", fill: "none", xmlns: "http://www.w3.org/2000/svg", ...e }, /* @__PURE__ */ o.createElement("g", { clipPath: "url(#clip0_141_42)" }, /* @__PURE__ */ o.createElement("mask", { id: "mask0_141_42", style: {
  maskType: "alpha"
}, maskUnits: "userSpaceOnUse", x: 0, y: 0, width: 16, height: 16 }, /* @__PURE__ */ o.createElement("path", { d: "M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16Z", fill: "white" })), /* @__PURE__ */ o.createElement("g", { mask: "url(#mask0_141_42)" }, /* @__PURE__ */ o.createElement("path", { d: "M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16Z", fill: "#46B450" }), /* @__PURE__ */ o.createElement("path", { d: "M5 8.24268L7 10.2427", stroke: "white", strokeWidth: 2, strokeLinecap: "round", strokeLinejoin: "round" }), /* @__PURE__ */ o.createElement("path", { d: "M7 10.2426L11.2426 6", stroke: "white", strokeWidth: 2, strokeLinecap: "round", strokeLinejoin: "round" }))), /* @__PURE__ */ o.createElement("defs", null, /* @__PURE__ */ o.createElement("clipPath", { id: "clip0_141_42" }, /* @__PURE__ */ o.createElement("rect", { width: 16, height: 16, fill: "white" })))), Ie = (e) => /* @__PURE__ */ o.createElement("svg", { width: 16, height: 16, viewBox: "0 0 16 16", fill: "none", xmlns: "http://www.w3.org/2000/svg", ...e }, /* @__PURE__ */ o.createElement("g", { clipPath: "url(#clip0_141_46)" }, /* @__PURE__ */ o.createElement("path", { d: "M16 8C16 12.4193 12.418 16 8 16C3.58203 16 0 12.4193 0 8C0 3.58332 3.58203 0 8 0C12.418 0 16 3.58332 16 8Z", fill: "#DC3232" }), /* @__PURE__ */ o.createElement("path", { d: "M10.8284 9.41435L6.58577 5.17171C6.19524 4.78118 5.56208 4.78118 5.17156 5.17171C4.78103 5.56223 4.78103 6.1954 5.17156 6.58592L9.4142 10.8286C9.80472 11.2191 10.4379 11.2191 10.8284 10.8286C11.2189 10.438 11.2189 9.80487 10.8284 9.41435Z", fill: "white" }), /* @__PURE__ */ o.createElement("path", { d: "M10.8284 6.58592C11.219 6.1954 11.219 5.56223 10.8284 5.17171C10.4379 4.78118 9.80476 4.78118 9.41423 5.17171L5.17159 9.41435C4.78107 9.80487 4.78107 10.438 5.17159 10.8286C5.56211 11.2191 6.19528 11.2191 6.5858 10.8286L10.8284 6.58592Z", fill: "white" })), /* @__PURE__ */ o.createElement("defs", null, /* @__PURE__ */ o.createElement("clipPath", { id: "clip0_141_46" }, /* @__PURE__ */ o.createElement("rect", { width: 16, height: 16, fill: "white" }))));
var ke = Object.defineProperty, B = Object.getOwnPropertySymbols, Ne = Object.prototype.hasOwnProperty, Le = Object.prototype.propertyIsEnumerable, Z = (e, s, t) => s in e ? ke(e, s, { enumerable: !0, configurable: !0, writable: !0, value: t }) : e[s] = t, A = (e, s) => {
  for (var t in s || (s = {}))
    Ne.call(s, t) && Z(e, t, s[t]);
  if (B)
    for (var t of B(s))
      Le.call(s, t) && Z(e, t, s[t]);
  return e;
};
const Fe = ({ iconName: e, iconAltText: s = "", children: t }) => {
  const a = {
    className: "icon-list-item-icon",
    alt: s || "",
    "aria-hidden": s === ""
  };
  return {
    do: /* @__PURE__ */ i.jsx($e, A({}, a)),
    dont: /* @__PURE__ */ i.jsx(Ie, A({}, a))
  }[e];
}, $ = ({ iconName: e, iconAltText: s = "", children: t }) => /* @__PURE__ */ i.jsxs("li", { className: `icon-list-item icon-list-item-${e}`, children: [
  /* @__PURE__ */ i.jsx(Fe, { iconName: e, iconAltText: s }),
  /* @__PURE__ */ i.jsx("span", { children: t })
] }), Te = () => /* @__PURE__ */ i.jsx("div", { className: "excludes-wrap", children: /* @__PURE__ */ i.jsx(
  N,
  {
    type: "warning",
    showIcon: !1,
    headerText: r("Which root files should be migrated?", "wp-migrate-db"),
    children: /* @__PURE__ */ i.jsxs(Ce, { children: [
      /* @__PURE__ */ i.jsx($, { iconName: "do", children: y(
        r(
          `<strong>Include</strong> content files (documents, media) served by
            this site to prevent 404 errors at the destination.`,
          "wp-migrate-db"
        )
      ) }),
      /* @__PURE__ */ i.jsx($, { iconName: "dont", children: y(
        r(
          `<strong>Exclude</strong> platform-specific files that may be
            incompatible with the destination.`,
          "wp-migrate-db"
        )
      ) }),
      /* @__PURE__ */ i.jsx($, { iconName: "dont", children: y(
        r(
          `<strong>Exclude</strong> plugin-generated files as they often contain
             hard-coded paths and can be regenerated if needed.`,
          "wp-migrate-db"
        )
      ) })
    ] })
  }
) }), Me = (e) => ({
  tables: "table",
  migrate: "migrate",
  import: "import",
  backup: "backup",
  media_files: "media",
  theme_files: "theme",
  themes: "theme",
  plugin_files: "plugin",
  plugins: "plugin",
  muplugin_files: "must-use plugin",
  muplugins: "must-use plugin",
  other_files: "other",
  others: "other",
  core_files: "core",
  core: "core",
  root_files: "root",
  root: "root",
  finalize: "finalize"
})[e.toLowerCase()] || e;
var De = Object.defineProperty, Ve = Object.defineProperties, We = Object.getOwnPropertyDescriptors, z = Object.getOwnPropertySymbols, Re = Object.prototype.hasOwnProperty, Ue = Object.prototype.propertyIsEnumerable, G = (e, s, t) => s in e ? De(e, s, { enumerable: !0, configurable: !0, writable: !0, value: t }) : e[s] = t, I = (e, s) => {
  for (var t in s || (s = {}))
    Re.call(s, t) && G(e, t, s[t]);
  if (z)
    for (var t of z(s))
      Ue.call(s, t) && G(e, t, s[t]);
  return e;
}, k = (e, s) => Ve(e, We(s));
const Be = (e, s, t) => {
  let a = [];
  if (!Array.isArray(s))
    return a;
  const c = s.map((l) => l.path);
  switch (t[`${e}_option`]) {
    case "selected":
      t[`${e}_selected`].forEach((l) => {
        c.includes(l) && a.push(l);
      });
      break;
    case "except":
      t[`${e}_excluded`].forEach((l) => {
        c.includes(l) && a.push(l);
      });
      break;
    case "active":
      s.forEach((l) => {
        l.active && a.push(l.path);
      });
      break;
    case "new_updated":
      s.forEach((l) => {
        const d = fe(
          l.sourceVersion,
          l.destinationVersion
        );
        ["add", "none", "up"].includes(d) && a.push(l.path);
      });
      break;
    case "all":
      a = c;
      break;
  }
  return a;
};
function Ze(e, s, t, a, c) {
  const l = Be(
    c,
    Object.values(s),
    e
  ), d = {
    themes: "theme_files",
    plugins: "plugin_files",
    muplugins: "muplugin_files",
    others: "other_files",
    core: "core_files",
    root: "root_files"
  }, { enabled: m = !1 } = e[d[c]] || {}, h = t.includes(d[c]), g = ge(a, {
    name: `SELECTED_${c.toUpperCase()}_EMPTY`
  });
  return { enabled: m, isOpen: h, selected: l, selectionEmpty: g };
}
const P = (e, s) => {
  const {
    theme_plugin_files: t,
    panelsOpen: a,
    current_migration: c,
    remote_site: l,
    local_site: d
  } = e, { status: m, intent: h } = c, g = le(e), f = oe(), { title: u, type: n, panel_name: _, items: E } = s, F = () => h === "savefile" ? E : E.filter((p) => p.path.includes("wpe-site-migration") === !1), T = E.map((p) => p.path), J = (p) => {
    t[`${n}_option`] === "except" && e.updateExcluded(p, n), e.updateSelected(p, n);
  };
  let j = !1;
  const K = ["push", "pull"].includes(h), M = k(I({}, K && {
    new_updated: w(
      r("New and updated %s versions", "wp-migrate-db"),
      Me(n)
    )
  }), {
    all: w(r("All %s", "wp-migrate-db"), n),
    active: w(r("Active %s", "wp-migrate-db"), n),
    selected: w(r("Selected %s", "wp-migrate-db"), n),
    except: w(
      r("All %s <b>except</b> those selected", "wp-migrate-db"),
      n
    )
  }), { enabled: O, isOpen: D, selected: x, selectionEmpty: V } = Ze(
    t,
    E,
    a,
    m,
    n
  ), Q = () => t[`${n}_option`] === "except" ? U(e.updateExcluded, T, x, n) : U(e.updateSelected, T, x, n), X = (p) => {
    const R = p.map((ne) => ne.path);
    t[`${n}_option`] === "except" ? e.updateExcluded(R, n) : e.updateSelected(R, n);
  };
  o.useEffect(() => {
    t[`${n}_option`] === "select" && f(e.updateSelected(x, n)), t[`${n}_option`] === "except" && e.updateExcluded(x, n);
  }, []), O && !D && (j = !0);
  const S = [], W = t[`${n}_option`] === "selected" || t[`${n}_option`] === "except";
  j && S.push("has-divider"), O && S.push("enabled");
  const ee = {
    themes: "theme",
    plugins: "plugin",
    muplugins: "must-use plugin",
    others: "file or directory",
    core: "file or directory",
    root: "file or directory"
  }, te = {
    muplugins: r(
      "Select any must-use plugins to be included in the migration.",
      "wp-migrate-db"
    ),
    others: r(
      "Select any other files and folders found in the <code>wp-content</code> directory to be included in the migration.",
      "wp-migrate-db"
    ),
    core: r(
      "Including WordPress core files ensures that the exported archive contains the exact version of WordPress installed on this site, which is helpful when replicating the site in a new environment. ",
      "wp-migrate-db"
    ),
    root: r(
      "Select any files and folders from your site's root directory to be included in the migration.",
      "wp-migrate-db"
    )
  }, se = ce(
    _,
    c,
    d,
    l
  );
  return /* @__PURE__ */ i.jsxs(
    de,
    {
      title: u,
      className: S.join(" "),
      panelName: _,
      disabled: g,
      writable: se,
      enabled: O,
      forceDivider: j,
      callback: (p) => he(
        p,
        _,
        D,
        O,
        g,
        e.addOpenPanel,
        e.removeOpenPanel,
        () => f(L(_))
      ),
      toggle: L(_),
      hasInput: !0,
      bodyClass: "tpf-panel-body",
      panelSummary: /* @__PURE__ */ i.jsx(
        Se,
        k(I({}, e), {
          disabled: g,
          items: F(),
          selectedOption: t[`${n}_option`],
          selected: x,
          title: u,
          type: _,
          summary: M[t[`${n}_option`]]
        })
      ),
      children: [
        /* @__PURE__ */ i.jsxs("div", { children: [
          ["others", "muplugins", "core", "root"].includes(n) && /* @__PURE__ */ i.jsxs("p", { className: "panel-instructions", children: [
            y(te[n]),
            n === "core" && /* @__PURE__ */ i.jsx(
              ue,
              {
                link: "https://deliciousbrains.com/wp-migrate-db-pro/doc/full-site-exports/",
                content: r(
                  "Learn When to Include Core Files",
                  "wp-migrate-db"
                ),
                utmContent: "wordpress-core-files-panel",
                utmCampaign: "wp-migrate-documentation",
                anchorLink: "wordpress-core-files"
              }
            )
          ] }),
          ["themes", "plugins"].includes(n) && /* @__PURE__ */ i.jsx(
            pe,
            {
              ariaLabel: w(r("%s options", "wp-migrate-db"), n),
              optionChoices: M,
              intent: "push",
              type: n,
              value: t[`${n}_option`],
              updateOption: Y
            }
          ),
          n !== "core" && /* @__PURE__ */ i.jsx(
            me,
            {
              id: `${n}-multiselect`,
              options: F(),
              extraLabels: "",
              stateManager: J,
              selected: x,
              visible: !0,
              disabled: !W,
              updateSelected: X,
              selectInverse: Q,
              showOptions: W,
              type: n,
              themePluginOption: t[`${n}_option`]
            }
          )
        ] }),
        !["core", "root"].includes(n) && /* @__PURE__ */ i.jsx("div", { className: "excludes-wrap excludes-wrap-full", children: /* @__PURE__ */ i.jsx(
          je,
          k(I({}, e), {
            excludes: t[`${n}_excludes`],
            excludesUpdater: e.updateExcludes,
            type: n
          })
        ) }),
        n === "root" && /* @__PURE__ */ i.jsx(Te, {}),
        V && t[`${n}_option`] === "selected" && /* @__PURE__ */ i.jsx(N, { type: "danger", children: w(
          r(
            "Please select at least one %s for migration",
            "wp-migrate-db"
          ),
          ee[n]
        ) }),
        V && t[`${n}_option`] === "except" && /* @__PURE__ */ i.jsx(N, { type: "danger", children: w(
          r(
            "Please select at least one %s to exclude from migration",
            "wp-migrate-db"
          ),
          n === "themes" ? "theme" : "plugin"
        ) })
      ]
    }
  );
};
var Ae = Object.defineProperty, ze = Object.defineProperties, Ge = Object.getOwnPropertyDescriptors, q = Object.getOwnPropertySymbols, qe = Object.prototype.hasOwnProperty, He = Object.prototype.propertyIsEnumerable, H = (e, s, t) => s in e ? Ae(e, s, { enumerable: !0, configurable: !0, writable: !0, value: t }) : e[s] = t, b = (e, s) => {
  for (var t in s || (s = {}))
    qe.call(s, t) && H(e, t, s[t]);
  if (q)
    for (var t of q(s))
      He.call(s, t) && H(e, t, s[t]);
  return e;
}, Ye = (e, s) => ze(e, Ge(s));
const Je = (e) => {
  const s = C("current_migration", e), t = C("local_site", e), a = C("remote_site", e), c = we("panelsOpen", e), l = be("stages", e), d = xe("status", e);
  return {
    theme_plugin_files: e.theme_plugin_files,
    current_migration: s,
    local_site: t,
    remote_site: a,
    panelsOpen: c,
    stages: l,
    status: d
  };
};
function v(e, s) {
  const t = {};
  return ["themes", "plugins", "muplugins", "others", "core", "root"].forEach(
    (a, c) => {
      const l = s === "pull" ? e.remote_site.site_details[a] : e.local_site.site_details[a], d = s === "pull" || s === "savefile" ? e.local_site.site_details[a] : e.remote_site.site_details[a];
      let m = l;
      const h = [], g = (f) => {
        if (d) {
          let u = d[f];
          if (u && u[0].hasOwnProperty("version"))
            return u[0].version;
        }
        return null;
      };
      for (const f in m) {
        let u = m[f];
        if (!u)
          continue;
        let n = {
          name: u[0].name,
          path: u[0].path,
          active: u[0].active
        };
        ["plugins", "themes"].includes(a) && s !== "savefile" && (n = Ye(b({}, n), {
          sourceVersion: u[0].version,
          destinationVersion: g(f)
        })), h.push(n);
      }
      return t[a] = h;
    }
  ), t;
}
const Ke = (e) => {
  const { intent: s } = e.current_migration, { themes: t } = v(e, s);
  return P(e, {
    title: r("Themes", "wp-migrate-db"),
    type: "themes",
    panel_name: "theme_files",
    items: t
  });
}, Qe = (e) => {
  const { intent: s } = e.current_migration, { plugins: t } = v(e, s);
  return P(e, {
    title: r("Plugins", "wp-migrate-db"),
    type: "plugins",
    panel_name: "plugin_files",
    items: t
  });
}, Xe = (e) => {
  const { intent: s } = e.current_migration, { muplugins: t } = v(e, s);
  return t.length === 0 ? null : P(e, {
    title: r("Must-Use Plugins", "wp-migrate-db"),
    type: "muplugins",
    panel_name: "muplugin_files",
    items: t
  });
}, et = (e) => {
  const { intent: s } = e.current_migration, { others: t } = v(e, s);
  return t.length === 0 ? null : P(e, {
    title: r("Other Files", "wp-migrate-db"),
    type: "others",
    panel_name: "other_files",
    items: t
  });
}, tt = (e) => {
  const { intent: s } = e.current_migration, { core: t } = v(e, s);
  return s !== "savefile" || t.length === 0 ? null : P(e, {
    title: r("WordPress Core Files", "wp-migrate-db"),
    type: "core",
    panel_name: "core_files",
    items: t
  });
}, st = (e) => {
  const { intent: s } = e.current_migration, { root: t } = v(e, s);
  return t.length === 0 ? null : P(e, {
    title: r("Root Files", "wp-migrate-db"),
    type: "root",
    panel_name: "root_files",
    items: t
  });
}, nt = (e) => /* @__PURE__ */ i.jsxs("div", { className: "theme-plugin-files", children: [
  /* @__PURE__ */ i.jsx(Ke, b({}, e)),
  /* @__PURE__ */ i.jsx(Qe, b({}, e)),
  /* @__PURE__ */ i.jsx(Xe, b({}, e)),
  /* @__PURE__ */ i.jsx(et, b({}, e)),
  /* @__PURE__ */ i.jsx(tt, b({}, e)),
  /* @__PURE__ */ i.jsx(st, b({}, e))
] }), at = _e(Je, {
  toggleThemePluginFiles: L,
  updateTPFOption: Y,
  updateSelected: Oe,
  updateExcluded: Ee,
  addOpenPanel: ye,
  removeOpenPanel: ve,
  updateExcludes: Pe
})(nt);
export {
  at as default
};
//# sourceMappingURL=ThemePluginFiles-CXXxKnyF.js.map
