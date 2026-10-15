{{-- Diagramme d'activités UML (couloirs) : de la demande au retour de rendement. Source PlantUML : docs/activites/activite_demande_a_rendement.puml --}}
<svg viewBox="0 0 690 1070" xmlns="http://www.w3.org/2000/svg" role="img" aria-labelledby="ad-titre ad-desc" class="ad-svg h-auto w-full min-w-[640px]">
<title id="ad-titre">Diagramme d'activités : de la demande au retour de rendement</title>
<desc id="ad-desc">Le DP saisit, fait visiter et soumet la demande ; la commission arbitre, attribue ou rejette, clôture et publie ; ensuite le règlement et la sortie de stock se font en parallèle de la transmission des rendements par le DP ; les rendements alimentent les statistiques.</desc>
<style>
.ad-svg{font-family:inherit}
.ad-lane{fill:#f8fafc;stroke:#cbd5e1}
.ad-head{fill:#1E5E3A}
.ad-ht{fill:#fff;font-size:13px;font-weight:700}
.ad-box{fill:#fff;stroke:#1E5E3A;stroke-width:1.5}
.ad-t{fill:#0f172a;font-size:12.5px}
.ad-g{fill:#475569;font-size:11.5px;font-style:italic}
.ad-l{fill:none;stroke:#475569;stroke-width:1.6}
.ad-m{fill:#475569}
.ad-n{fill:#0f172a}
.ad-dec{fill:#fef3c7;stroke:#E5A93B;stroke-width:1.5}
.ad-bar{fill:#0f172a}
.ad-ring{fill:#fff;stroke:#0f172a;stroke-width:1.6}
.ad-obj{fill:#ecfdf5;stroke:#1E5E3A;stroke-width:1.5}
@media (prefers-color-scheme:dark){.ad-lane{fill:#1e293b;stroke:#475569}.ad-box,.ad-obj{fill:#0f172a}.ad-t,.ad-ht{fill:#f1f5f9}.ad-dec{fill:#451a03}.ad-l{stroke:#94a3b8}.ad-m{fill:#94a3b8}.ad-g{fill:#94a3b8}.ad-n,.ad-bar{fill:#f1f5f9}.ad-ring{fill:#0f172a;stroke:#f1f5f9}}
</style>
<defs><marker id="ad-fl" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" class="ad-m"/></marker></defs>
<rect x="0" y="0" width="230" height="1070" class="ad-lane"/>
<rect x="0" y="0" width="230" height="34" class="ad-head"/>
<text x="115" y="22" text-anchor="middle" class="ad-ht">Délégué de pôle (DP)</text>
<rect x="230" y="0" width="230" height="1070" class="ad-lane"/>
<rect x="230" y="0" width="230" height="34" class="ad-head"/>
<text x="345" y="22" text-anchor="middle" class="ad-ht">Commission et administration</text>
<rect x="460" y="0" width="230" height="1070" class="ad-lane"/>
<rect x="460" y="0" width="230" height="34" class="ad-head"/>
<text x="575" y="22" text-anchor="middle" class="ad-ht">Magasin et caisse</text>
<circle cx="115" cy="62" r="9" class="ad-n"/>
<polyline points="115,71 115,95" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="17.0" y="95.0" width="196" height="50" rx="10" class="ad-box"/>
<text x="115" y="116.5" text-anchor="middle" class="ad-t">Saisir la demande</text>
<text x="115" y="131.5" text-anchor="middle" class="ad-t">(superficies et appuis)</text>
<polyline points="115,145 115,175" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="17.0" y="175.0" width="196" height="50" rx="10" class="ad-box"/>
<text x="115" y="196.5" text-anchor="middle" class="ad-t">Enregistrer la visite</text>
<text x="115" y="211.5" text-anchor="middle" class="ad-t">de terrain</text>
<polyline points="115,225 115,262" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="17.0" y="262.0" width="196" height="36" rx="10" class="ad-box"/>
<text x="115" y="284.0" text-anchor="middle" class="ad-t">Soumettre la demande</text>
<polyline points="115,298 115,348 247,348" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="247.0" y="323.0" width="196" height="50" rx="10" class="ad-box"/>
<text x="345" y="344.5" text-anchor="middle" class="ad-t">Arbitrer en séance</text>
<text x="345" y="359.5" text-anchor="middle" class="ad-t">(demandes face au stock)</text>
<polyline points="345,373 345,408" class="ad-l" marker-end="url(#ad-fl)"/>
<polygon points="345,408 405,440 345,472 285,440" class="ad-dec"/>
<text x="345" y="444" text-anchor="middle" class="ad-t">Accordée ?</text>
<polyline points="285,440 213,440" class="ad-l" marker-end="url(#ad-fl)"/>
<text x="236" y="432" class="ad-g">non</text>
<rect x="17.0" y="415.0" width="196" height="50" rx="10" class="ad-box"/>
<text x="115" y="436.5" text-anchor="middle" class="ad-t">Rejet notifié au DP</text>
<text x="115" y="451.5" text-anchor="middle" class="ad-t">(motif)</text>
<polyline points="115,465 115,501" class="ad-l" marker-end="url(#ad-fl)"/>
<circle cx="115" cy="512" r="11" class="ad-ring"/><circle cx="115" cy="512" r="5.5" class="ad-n"/>
<polyline points="345,472 345,524" class="ad-l" marker-end="url(#ad-fl)"/>
<text x="353" y="498" class="ad-g">oui</text>
<rect x="247.0" y="524.0" width="196" height="36" rx="10" class="ad-box"/>
<text x="345" y="546.0" text-anchor="middle" class="ad-t">Attribuer les quantités</text>
<polyline points="345,560 345,585" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="247.0" y="585.0" width="196" height="50" rx="10" class="ad-box"/>
<text x="345" y="606.5" text-anchor="middle" class="ad-t">Clôturer la séance</text>
<text x="345" y="621.5" text-anchor="middle" class="ad-t">(l'attribution est validée)</text>
<polyline points="345,635 345,665" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="247.0" y="665.0" width="196" height="50" rx="10" class="ad-box"/>
<text x="345" y="686.5" text-anchor="middle" class="ad-t">Publier les résultats</text>
<text x="345" y="701.5" text-anchor="middle" class="ad-t">(le DP est notifié)</text>
<polyline points="345,715 345,752" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="95" y="752" width="500" height="6" rx="3" class="ad-bar"/>
<polyline points="115,758 115,797" class="ad-l" marker-end="url(#ad-fl)"/>
<polyline points="575,758 575,788" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="17.0" y="797.0" width="196" height="66" rx="10" class="ad-box"/>
<text x="115" y="819.0" text-anchor="middle" class="ad-t">Transmettre le rendement</text>
<text x="115" y="834.0" text-anchor="middle" class="ad-t">de chaque superficie</text>
<text x="115" y="849.0" text-anchor="middle" class="ad-t">(ou l'admin à sa place)</text>
<rect x="477.0" y="788.0" width="196" height="48" rx="10" class="ad-box"/>
<text x="575" y="808.5" text-anchor="middle" class="ad-t">Enregistrer le règlement</text>
<text x="575" y="823.5" text-anchor="middle" class="ad-t">(en une ou plusieurs fois)</text>
<polyline points="575,836 575,868" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="477.0" y="868.0" width="196" height="36" rx="10" class="ad-box"/>
<text x="575" y="890.0" text-anchor="middle" class="ad-t">Sortie de stock sur bon</text>
<polyline points="115,863 115,930" class="ad-l" marker-end="url(#ad-fl)"/>
<polyline points="575,904 575,930" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="95" y="930" width="500" height="6" rx="3" class="ad-bar"/>
<polyline points="345,936 345,956" class="ad-l" marker-end="url(#ad-fl)"/>
<rect x="247.0" y="956.0" width="196" height="48" rx="10" class="ad-box"/>
<text x="345" y="976.5" text-anchor="middle" class="ad-t">Analyser les rendements</text>
<text x="345" y="991.5" text-anchor="middle" class="ad-t">par écologie (statistiques)</text>
<polyline points="345,1004 345,1031" class="ad-l" marker-end="url(#ad-fl)"/>
<circle cx="345" cy="1042" r="11" class="ad-ring"/><circle cx="345" cy="1042" r="5.5" class="ad-n"/>
</svg>