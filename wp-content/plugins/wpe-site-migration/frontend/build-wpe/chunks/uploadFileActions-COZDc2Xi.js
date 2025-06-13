import { s as c, l as g, n as M, o as y, p as d, q as E, u as S, v as F, w as z, x as m, y as I, z as v, A as P, B as k, C as D, E as O, F as b, I as w } from "./indexWPE-Bsx8TyLp.js";
var f = (r, e, i) => new Promise((s, t) => {
  var l = (o) => {
    try {
      n(i.next(o));
    } catch (u) {
      t(u);
    }
  }, a = (o) => {
    try {
      n(i.throw(o));
    } catch (u) {
      t(u);
    }
  }, n = (o) => o.done ? s(o.value) : Promise.resolve(o.value).then(l, a);
  n((i = i.apply(r, e)).next());
});
const L = (r) => (e, i) => e(c(g, r)), A = (r) => (e, i) => f(void 0, null, function* () {
  const s = M("import_gzipped", i()), t = y("file_size", i()), l = {
    chunk: r.chunk,
    current_query: r.current_query,
    import_file: r.import_filename
  };
  l.import_info = JSON.stringify({
    import_gzipped: s
  });
  let a;
  try {
    a = yield d("/import-file", l);
  } catch (p) {
    return e(E(p)), !1;
  }
  const n = a.data, { table_sizes: o, table_rows: u, tables: _ } = n;
  e(L({ table_sizes: o, table_rows: u, tables: _ }));
  const R = Math.ceil(t / n.num_chunks) / 1e3;
  if (e(S(R)), n.chunk >= n.num_chunks)
    return e(F()), e(c(z, "import")), e(m("MIGRATE", [], "find_replace"));
  {
    const p = [
      {
        import_filename: r.import_filename,
        item_name: r.item_name,
        chunk: n.chunk,
        current_query: n.current_query
      }
    ];
    return yield e(
      m("IMPORT_FILE", [
        {
          fn: A,
          args: p
        }
      ])
    );
  }
}), T = 1e3 * 1024, U = (r) => (e, i) => f(void 0, null, function* () {
  e(c(v, "import")), e(c(O));
  let s;
  try {
    s = yield d("/prepare-upload", {});
  } catch (n) {
    return e(E(n)), !1;
  }
  var t = r.name;
  const l = window.wpmdb_strings.importing_file_to_db.replace(
    /%s\s?/,
    t
  );
  e(c(b, l)), t.slice(-3) === ".gz" && (t = r.name.slice(0, -3));
  const a = [
    {
      import_filename: s.data.import_file,
      item_name: t,
      chunk: 0,
      current_query: ""
    }
  ];
  return yield e(
    m(w, [
      {
        fn: A,
        args: a
      }
    ])
  );
}), q = (r) => (e, i) => f(void 0, null, function* () {
  const s = I("remote_site", i());
  r = typeof r > "u" ? 0 : r;
  const t = y("file", i());
  var l = r + T + 1, a = new FileReader();
  r === 0 && (e(c(v, "upload")), e(
    P(
      Math.ceil(y("file_size", i()) / 1e3)
    )
  )), a.onloadend = (o) => f(void 0, null, function* () {
    if (o.target.readyState !== FileReader.DONE)
      return;
    const u = {
      action: "wpmdb_upload_file",
      file_data: o.target.result,
      file: t.name,
      file_type: t.type,
      stage: "import",
      import_info: s
    };
    try {
      yield d("/upload-file", u);
    } catch (_) {
      return e(E(_)), !1;
    }
    if (l < t.size)
      return e(S(Math.ceil(T / 1e3))), yield e(
        m(k, [
          {
            fn: q,
            args: [l]
          }
        ])
      );
    {
      const _ = t.size - r;
      return e(S(Math.ceil(_ / 1e3))), e(c(z, "upload")), yield e(
        m(D, [
          {
            fn: U,
            args: [t]
          }
        ])
      );
    }
  });
  var n = t.slice(r, l);
  a.readAsDataURL(n);
});
export {
  A as importFile,
  L as setImportTableData,
  q as uploadFileActions
};
//# sourceMappingURL=uploadFileActions-COZDc2Xi.js.map
