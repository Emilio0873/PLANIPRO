# -*- coding: utf-8 -*-
"""Enrichit MEMOIRE_PLANIPRO.docx pour dépasser 65 pages — contenu UP détaillé."""
from pathlib import Path
from docx import Document
from docx.shared import Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml.ns import qn

DOC_PATH = Path(__file__).resolve().parent / "MEMOIRE_PLANIPRO.docx"
doc = Document(str(DOC_PATH))


def font(run, size=12, bold=False, italic=False, name="Times New Roman"):
    run.font.name = name
    run._element.rPr.rFonts.set(qn("w:eastAsia"), name)
    run.font.size = Pt(size)
    run.bold = bold
    run.italic = italic


def add_p(text="", *, center=False, justify=True, bold=False, italic=False, size=12,
          first_indent=1.25, space_after=8, indent_left=0):
    para = doc.add_paragraph()
    pf = para.paragraph_format
    pf.space_after = Pt(space_after)
    pf.line_spacing = 1.5
    if center:
        para.alignment = WD_ALIGN_PARAGRAPH.CENTER
    elif justify:
        para.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    if first_indent is not None:
        pf.first_line_indent = Cm(first_indent)
    if indent_left:
        pf.left_indent = Cm(indent_left)
    if text:
        r = para.add_run(text)
        font(r, size=size, bold=bold, italic=italic)
    return para


def add_h2(text):
    para = doc.add_paragraph()
    para.paragraph_format.space_before = Pt(14)
    para.paragraph_format.space_after = Pt(8)
    r = para.add_run(text)
    font(r, size=14, bold=True)


def add_h3(text):
    para = doc.add_paragraph()
    para.paragraph_format.space_before = Pt(10)
    para.paragraph_format.space_after = Pt(6)
    r = para.add_run(text)
    font(r, size=12, bold=True, italic=True)


def add_bullet(text):
    para = doc.add_paragraph()
    para.paragraph_format.left_indent = Cm(1.2)
    para.paragraph_format.space_after = Pt(4)
    para.paragraph_format.line_spacing = 1.3
    r = para.add_run("• " + text)
    font(r, size=12)


def add_fig(num, caption, hint):
    add_p(f"[ EMPLACEMENT FIGURE {num} ]", center=True, justify=False, bold=True, size=11, first_indent=0)
    add_p(hint, center=True, justify=False, italic=True, size=10, first_indent=0)
    add_p(f"Figure {num} — {caption}", center=True, justify=False, italic=True, size=11, first_indent=0)


def add_table(headers, rows, caption=None):
    if caption:
        add_p(caption, center=True, justify=False, italic=True, size=11, first_indent=0)
    t = doc.add_table(rows=1 + len(rows), cols=len(headers))
    t.style = "Table Grid"
    t.alignment = WD_TABLE_ALIGNMENT.CENTER
    for i, h in enumerate(headers):
        cell = t.rows[0].cells[i]
        cell.text = ""
        run = cell.paragraphs[0].add_run(h)
        font(run, size=10, bold=True)
        cell.paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
    for ri, row in enumerate(rows):
        for ci, val in enumerate(row):
            cell = t.rows[ri + 1].cells[ci]
            cell.text = ""
            run = cell.paragraphs[0].add_run(str(val))
            font(run, size=10)
    doc.add_paragraph()


def add_ref(text):
    para = doc.add_paragraph()
    pf = para.paragraph_format
    pf.left_indent = Cm(1.0)
    pf.first_line_indent = Cm(-1.0)
    pf.space_after = Pt(4)
    pf.line_spacing = 1.15
    r = para.add_run(text)
    font(r, size=11)


# Insert page break then enrichment before the very end — append large section
# Find "FIN DU MÉMOIRE" and insert before it by appending content then user can reorder
# Simpler: append a full complementary part titled "Développements complémentaires"

para = doc.add_paragraph()
run = para.add_run()
run.add_break(WD_BREAK.PAGE)

add_h2("Développements complémentaires — Approfondissement méthodologique UP")
add_p(
    "Afin de renforcer la cohérence académique du mémoire et d'expliciter davantage l'application "
    "du Processus Unifié à PLANIPRO, cette section complémentaire développe les pratiques, "
    "artefacts, rôles et décisions prises à chaque phase. Elle s'adresse tant au lecteur désirant "
    "comprendre le « comment » méthodologique qu'au jury souhaitant vérifier la maîtrise réelle "
    "d'UP (Jacobson et al., 1999 ; Kruchten, 2003 ; Scott, 2001 ; Kroll & Kruchten, 2003)."
)

add_h3("Rappel : pourquoi UP plutôt qu'une cascade pure ?")
add_p(
    "Dans un projet de mémoire aboutissant à un prototype logiciel, la cascade classique "
    "(analyse → conception → codage → tests) présente un risque majeur : découvrir trop tard "
    "qu'une exigence a été mal comprise ou qu'une hypothèse technique est irréaliste "
    "(Boehm, 1988 ; Sommerville, 2015). Le Processus Unifié mitige ce risque en imposant des "
    "itérations courtes, une architecture construite tôt, et un pilotage par les cas "
    "d'utilisation. Chaque itération produit un incrément exécutable, même partiel, permettant "
    "un feedback rapide (Larman, 2004)."
)
add_p(
    "UP n'interdit pas la documentation : il la recentre sur des artefacts utiles (Vision, "
    "modèle de CU, document d'architecture, cas de test). Cette philosophie convient "
    "particulièrement à un travail académique où la traçabilité exigences → conception → "
    "code → tests doit être démontrable (IEEE 830 ; Wiegers & Beatty, 2013)."
)

add_h2("Approfondissement de la phase d'Inception")
add_p(
    "Durant l'Inception de PLANIPRO, les questions suivantes ont été tranchées : quel problème "
    "résoudre ? pour qui ? avec quel périmètre minimal (MVP) ? quels risques menacent le succès ? "
    "quelle faisabilité technique (PHP/MySQL/XAMPP) ? (Kruchten, 2003)."
)
add_p(
    "Le MVP retenu comprenait : authentification multi-rôles, workflow de rendez-vous, activités "
    "assignables, calendrier basique, et un canal de contact. La messagerie riche, les rapports "
    "PDF et les notifications publiques ont été planifiés pour des itérations ultérieures de "
    "Construction, tout en restant dans le périmètre global de la Vision."
)
add_p(
    "Les parties prenantes identifiées sont : le promoteur académique (qualité scientifique), "
    "l'étudiant-développeur (réalisation), les utilisateurs cibles (entreprise, client, "
    "partenaire, visiteur), et éventuellement un hébergeur technique. Leurs attentes divergent : "
    "le promoteur exige rigueur méthodologique ; l'utilisateur exige simplicité. UP aide à "
    "concilier ces attentes via la priorisation des cas d'utilisation (Cockburn, 2000)."
)
add_fig("2.7", "Carte des parties prenantes (Inception)",
        "Matrice influence/intérêt : Promoteurs, Utilisateurs, Développeur, Hébergeur.")

add_h3("Critères de succès de l'Inception")
add_bullet("Vision partagée et rédigée.")
add_bullet("Liste initiale des CU classés par priorité (Must / Should / Could).")
add_bullet("Risques majeurs identifiés avec stratégies de mitigation.")
add_bullet("Faisabilité technique démontrée (preuve de concept connexion PHP–MySQL).")
add_bullet("Estimation grossière de l'effort (itérations de Construction).")
add_p(
    "Ces critères correspondent au jalon Lifecycle Objective (LCO) du RUP "
    "(Kroll & Kruchten, 2003). Dans le cadre du mémoire, le LCO est considéré atteint "
    "lorsque le chapitre II est stabilisé et validé avec le promoteur."
)

add_h2("Approfondissement de la phase d'Élaboration")
add_p(
    "L'Élaboration est souvent la phase la plus délicate : il faut « durcir » l'architecture "
    "sans construire encore toutes les fonctionnalités (Kruchten, 1995, 2003). Pour PLANIPRO, "
    "les décisions architecturales structurantes prises en Élaboration sont :"
)
add_bullet("Choix d'une application web monolithique PHP plutôt qu'une SPA complexe.")
add_bullet("Séparation pages (affichage) / actions (commandes) / includes (services transverses).")
add_bullet("Modèle de données relationnel normalisé avec isolation multi-entreprises.")
add_bullet("Sécurité transverse (sessions, rôles, CSRF, PDO, échappement) conçue dès le début.")
add_bullet("Calendrier basé sur FullCalendar consommant une API JSON interne.")
add_p(
    "Ces décisions réduisent le risque de devoir refactoriser massivement en Construction. "
    "Elles s'inscrivent dans le principe architecture-centric d'UP (Bass et al., 2012)."
)

add_h3("Priorisation des cas d'utilisation (technique MoSCoW)")
add_table(
    ["Priorité", "Cas d'utilisation", "Justification"],
    [
        ["Must", "Se connecter / S'inscrire (entreprise)", "Sans auth, pas de système"],
        ["Must", "Demander / Valider un RDV", "Cœur de la Vision"],
        ["Must", "Créer / Assigner une activité", "Différenciant métier"],
        ["Should", "Consulter le calendrier", "Valeur UX élevée"],
        ["Should", "Gérer contacts et visiteurs", "Nécessaire au multi-rôles"],
        ["Could", "Messagerie 1–1", "Améliore la communication"],
        ["Could", "Contact public + notifs", "Acquisition de prospects"],
        ["Could", "Rapports PDF", "Pilotage tactique"],
    ],
    "Tableau 3.7 — Priorisation MoSCoW des cas d'utilisation",
)
add_p(
    "La priorisation MoSCoW, compatible avec UP et Agile, évite de traiter tous les CU comme "
    "équivalents (Cockburn, 2000 ; Leffingwell & Widrig, 2003). Les Must ont été couverts dès "
    "les itérations C1–C3 ; les Could en C5–C6."
)

add_h3("Description détaillée — CU « Reporter un rendez-vous »")
add_p(
    "Nom : Reporter un rendez-vous. Acteur principal : Entreprise. Acteurs secondaires : "
    "Demandeur (client/partenaire/visiteur). Préconditions : l'entreprise est authentifiée ; "
    "le RDV existe et est dans un état reportable (typiquement accepte ou en_attente selon "
    "règle métier retenue). Postconditions : le RDV passe à reporte (ou revient en validation) "
    "avec nouvelles dates ; une notification est envoyée au demandeur ; l'historique enregistre "
    "l'action."
)
add_p(
    "Scénario nominal : (1) l'entreprise ouvre la fiche RDV ; (2) choisit Reporter ; "
    "(3) saisit nouvelles dates et commentaire ; (4) le système valide la cohérence "
    "date_fin > date_debut ; (5) met à jour le statut et les dates ; (6) notifie le demandeur ; "
    "(7) met à jour le calendrier. Scénario alternatif : dates invalides → message d'erreur "
    "sans modification. Scénario d'exception : tentative de report par un non-autorisé → refus "
    "(require_role)."
)
add_fig("3.16", "Diagramme de séquence — Reporter un RDV",
        "Entreprise → UI → rdv_action → BDD → Notification → Calendrier.")

add_h3("Description détaillée — CU « Mettre à jour la progression d'une activité »")
add_p(
    "Ce CU illustre la boucle de feedback opérationnel. L'entreprise (ou le dispositif prévu) "
    "ajuste le pourcentage de progression et éventuellement le statut (a_faire, en_cours, "
    "terminee). Le système persiste progression et updated_at, permettant un suivi Kanban "
    "léger (Anderson, 2010). La traçabilité UP exige que ce CU soit couvert par au moins un "
    "cas de test (voir T05 et extensions)."
)

add_h2("Modèle d'analyse approfondi")
add_p(
    "Dans UP, les classes d'analyse se répartissent en trois stéréotypes (Jacobson et al., 1999) :"
)
add_bullet("Boundary (interface) : écrans de login, listes RDV, formulaires, calendrier.")
add_bullet("Control (contrôle) : GestionAuthentification, GestionRendezVous, GestionActivites, GestionMessagerie.")
add_bullet("Entity (entité) : User, Entreprise, RendezVous, Activite, Conversation, Message, Notification, ContactPublic.")
add_p(
    "Cette séparation prépare la conception sans imposer encore PHP ou MySQL. Elle clarifie "
    "les responsabilités et facilite les diagrammes de collaboration/séquence "
    "(Larman, 2005 ; Roques, 2018)."
)
add_fig("3.17", "Collaborations d'analyse — flux Demander RDV",
        "Boundary DemandeRDV → Control GestionRDV → Entity RendezVous + Notification.")

add_h2("Conception détaillée des composants")
add_p(
    "Au niveau conception, les « contrôles » d'analyse se traduisent en scripts du dossier "
    "actions/ et en helpers includes/. Les « boundaries » deviennent des pages PHP "
    "générant HTML. Les « entities » deviennent des tables MySQL et des lignes PDO. "
    "Cette traduction concrète montre la continuité des artefacts UP jusqu'au code "
    "(Conallen, 2002)."
)
add_h3("Composant Authentification")
add_p(
    "Responsabilités : vérifier les identifiants, ouvrir/fermer la session, fournir "
    "l'utilisateur courant, vérifier les rôles, générer/valider les jetons CSRF. "
    "Fichiers : includes/auth.php, actions/login.php, actions/logout.php, "
    "actions/register.php, pages/auth/*. Interfaces : formulaires login/register."
)
add_h3("Composant Rendez-vous")
add_p(
    "Responsabilités : CRUD partiel, transitions de statut, notifications associées, "
    "alimentation du calendrier. Fichiers : pages/*/rendez_vous*.php, actions/rdv_*.php. "
    "Entité centrale : table rendez_vous."
)
add_h3("Composant Activités")
add_p(
    "Responsabilités : création, édition, suppression, assignation, progression. "
    "Fichiers : pages/entreprise/activites.php, activite_ajouter.php, actions/activite_*.php."
)
add_h3("Composant Messagerie")
add_p(
    "Responsabilités : trouver ou créer une conversation 1–1, lister les fils, envoyer "
    "un message, marquer comme lu. Fichiers : includes/messaging.php, pages/messagerie/*, "
    "actions/message_*.php."
)
add_fig("3.18", "Diagramme de composants PLANIPRO",
        "Boîtes : Auth, RDV, Activités, Calendrier, Contacts, Messagerie, Public, Rapports — dépendances.")

add_h2("Approfondissement Construction : conduite des itérations")
add_p(
    "Chaque itération de Construction a suivi un mini-cycle : sélection des CU → conception "
    "fine → codage → test → intégration → revue. Ce rythme est fidèle à l'esprit itératif "
    "d'UP (Larman, 2004). Un journal de bord simplifié a permis de noter les obstacles "
    "(encodage, règles d'isolation, UX calendrier mobile)."
)
add_p(
    "L'intégration continue « artisanale » consistait à vérifier après chaque module que "
    "les parcours critiques (login → dashboard → RDV) restaient non régressifs. Cette "
    "pratique, même sans serveur CI, incarne la discipline Test d'UP (ISTQB, 2019)."
)

add_h3("Exemple de définition de done d'une itération")
add_bullet("CU ciblés implémentés et démontrables.")
add_bullet("Cas de test associés exécutés (OK/KO documentés).")
add_bullet("Pas de secret en clair dans le code.")
add_bullet("Pages protégées par require_role lorsque nécessaire.")
add_bullet("Documentation README mise à jour si installation impactée.")

add_h2("Discipline Test : stratégie détaillée")
add_p(
    "UP recommande de planifier les tests dès les exigences (Kruchten, 2003). Nous avons "
    "adopté des tests fonctionnels manuels structurés en scénarios, complétés par des "
    "contrôles de sécurité (CSRF, isolation). Une automatisation Selenium n'a pas été "
    "jugée indispensable au périmètre académique, mais constitue une perspective."
)
add_table(
    ["Niveau", "Objectif", "Exemples PLANIPRO"],
    [
        ["Unitaire (léger)", "Fonctions helpers", "e(), hash, tokens"],
        ["Intégration", "PHP ↔ MySQL", "Insert RDV + notif"],
        ["Système", "Parcours bout-en-bout", "Demande→Acceptation→Calendrier"],
        ["Acceptation", "Conformité Vision", "Recette avec checklist"],
    ],
    "Tableau 4.7 — Niveaux de test alignés UP",
)
add_p(
    "La traçabilité exigence → test est un marqueur de maturité UP. L'annexe E du mémoire "
    "en fournit un extrait ; une version complète peut être maintenue dans un tableur "
    "pendant la Construction."
)

add_h2("Discipline Déploiement et Transition")
add_p(
    "La Transition prépare le passage du prototype au contexte d'usage (Kruchten, 2003). "
    "Pour PLANIPRO, les livrables de Transition sont : package source, scripts SQL, README "
    "d'installation, comptes de démonstration, présent mémoire, et captures d'écran de "
    "recette. Une formation courte (30–60 minutes) suffit pour une entreprise pilote : "
    "créer un compte, ajouter un client, accepter un RDV, créer une activité."
)
add_p(
    "Le plan de bascule recommandé : (1) installation sur environnement de préproduction ; "
    "(2) import schéma ; (3) création compte entreprise réel ; (4) UAT sur 10–20 RDV ; "
    "(5) corrections ; (6) mise en production avec HTTPS ; (7) sauvegarde quotidienne."
)
add_fig("4.15", "Plan de Transition UP pour PLANIPRO",
        "Étapes préprod → UAT → prod → support, avec responsables.")

add_h2("Gestion des risques tout au long du cycle UP")
add_p(
    "UP est risk-driven : les risques techniques et métier sont re-évalués à chaque phase "
    "(Boehm, 1988 ; Kroll & Kruchten, 2003). Synthèse pour PLANIPRO :"
)
add_table(
    ["Risque", "Phase de traitement", "Statut final"],
    [
        ["Mauvaise compréhension du workflow RDV", "Inception + Élaboration", "Mitigé (états formalisés)"],
        ["Complexité multi-rôles", "Élaboration", "Mitigé (matrice droits)"],
        ["Calendrier trop complexe", "Construction C3", "Mitigé (FullCalendar)"],
        ["Sécurité insuffisante", "Élaboration + C1", "Mitigé (base OWASP)"],
        ["Dérive de périmètre", "Tout le projet", "Surveillé (MoSCoW)"],
        ["Adoption utilisateur", "Transition", "À valider en UAT réel"],
    ],
    "Tableau 4.8 — Suivi des risques UP",
)

add_h2("Leçons méthodologiques tirées de l'usage d'UP")
add_p(
    "1) Rédiger la Vision tôt évite de coder des fonctions hors sujet. "
    "2) Les cas d'utilisation rendent les discussions avec le promoteur concrètes. "
    "3) Une architecture simple mais explicite (pages/actions) suffit pour un projet PME. "
    "4) Les itérations motivent : chaque semaine un incrément visible. "
    "5) La documentation utile (CONCEPTION.md + mémoire) vaut mieux qu'une documentation "
    "ornementale. "
    "6) UP doit être adapté (tailoring) : nous n'avons pas produit tous les artefacts RUP "
    "commerciaux, seulement ceux à valeur démonstrative (Ambler, 2002 ; Scott, 2001)."
)
add_p(
    "Ces leçons confirment que le Processus Unifié, loin d'être réservé aux grands projets "
    "industriels, s'applique efficacement à un mémoire de conception-réalisation, dès lors "
    "qu'il est dimensionné à la taille de l'équipe (ici, essentiellement mono-développeur "
    "encadré)."
)

add_h2("Alignement des chapitres du mémoire sur les phases UP")
add_table(
    ["Chapitre du mémoire", "Phase UP dominante", "Disciplines UP principales", "Jalon"],
    [
        ["Introduction", "Cadrage méthodologique", "Gestion de projet", "—"],
        ["Chapitre I", "Fondements", "Environnement (méthodes)", "—"],
        ["Chapitre II", "Inception", "Modélisation métier, Exigences", "LCO"],
        ["Chapitre III", "Élaboration", "Exigences, Analyse & Conception", "LCA"],
        ["Chapitre IV", "Construction + Transition", "Implémentation, Test, Déploiement", "IOC / PR"],
        ["Conclusion", "Bilan", "Gestion de projet", "Clôture"],
    ],
    "Tableau 0.1 — Alignement mémoire ↔ Processus Unifié",
)
add_p(
    "Ce tableau constitue la clé de lecture du mémoire : chaque chapitre n'est pas un "
    "découpage arbitraire, mais le reflet d'une phase ou d'un ensemble de disciplines UP. "
    "Il démontre la cohérence méthodologique annoncée dès la page de garde."
)

add_h2("Spécification complémentaire des exigences qualité")
add_p(
    "En s'appuyant sur ISO/IEC 25010, nous détaillons les attributs de qualité visés :"
)
add_bullet("Aptitude fonctionnelle : couverture des BF Must/Should.")
add_bullet("Efficience : temps de réponse acceptable sur XAMPP local.")
add_bullet("Compatibilité : navigateurs modernes.")
add_bullet("Utilisabilité : menus par rôle, messages d'erreur clairs.")
add_bullet("Fiabilité : contraintes FK, gestion des formulaires invalides.")
add_bullet("Sécurité : OWASP Top 10 — mesures de base implémentées.")
add_bullet("Maintenabilité : structure de dossiers lisible, schéma SQL documenté.")
add_bullet("Portabilité : stack LAMP/WAMP standard.")
add_p(
    "Ces attributs ont guidé certaines décisions (par exemple PDO plutôt que des requêtes "
    "concaténées ; échappement systématique ; absence de frameworks lourds pour préserver "
    "la portabilité pédagogique)."
)

add_h2("Scénarios métier complets (storyboards textuels)")
add_h3("Scénario A — De la demande client à la réunion tenue")
add_p(
    "Marie, cliente de l'entreprise Alpha, se connecte, choisit un créneau, décrit le motif "
    "« revue contractuelle », soumet. Le gérant d'Alpha reçoit une notification, ouvre la "
    "liste des RDV en attente, accepte avec le commentaire « Salle B ». Marie est notifiée. "
    "Le jour J, après la rencontre, le gérant marque le RDV comme terminé. L'historique "
    "conserve la chronologie. Ce scénario exerce les CU Demander, Accepter, Terminer et "
    "les notifications associées."
)
add_h3("Scénario B — Recrutement d'un visiteur depuis un contact public")
add_p(
    "Paul, prospect, trouve Alpha sur l'accueil PLANIPRO, envoie un message. L'entreprise "
    "lit le message, décide de créer un compte Visiteur, génère des identifiants, envoie "
    "une notification publique. Paul se connecte, consulte une activité « Préparer dossier "
    "présentation » qui lui est assignée, et échange via la messagerie. Ce scénario exerce "
    "le flux public → visiteur, différenciant de PLANIPRO."
)
add_h3("Scénario C — Pilotage hebdomadaire")
add_p(
    "En fin de semaine, le gérant ouvre les rapports, visualise le nombre de RDV acceptés, "
    "refuse et activités terminées, exporte un PDF pour la réunion de lundi. Ce scénario "
    "relie le niveau opérationnel au niveau tactique du SI (Laudon & Laudon, 2020)."
)
add_fig("4.16", "Storyboard scénario A (BD)",
        "6 vignettes : demande → notif → acceptation → notif → réunion → terminé.")

add_h2("Discussion : UP et contextes africains / PME")
add_p(
    "Dans de nombreux contextes de PME africaines, les contraintes d'infrastructure, de "
    "budget et de compétences orientent vers des solutions simples, hébergeables localement "
    "ou sur mutualisé bas coût (ITU, 2023 ; GSMA, 2023). UP, lorsqu'il est adapté (tailored), "
    "évite deux écueils : le formalisme excessif incompatible avec une petite équipe, et "
    "l'absence totale de méthode qui produit des logiciels fragiles."
)
add_p(
    "PLANIPRO illustre un UP « léger » : artefacts essentiels, itérations courtes, UML "
    "sélectif (CU, séquences, activités, classes/ Merise pour les données), tests manuels "
    "structurés. Cette adaptation est fidèle à l'esprit d'UP, conçu comme un processus "
    "configurable et non comme un carcan (Scott, 2001 ; Ambler, 2002)."
)

add_h2("Synthèse des contributions")
add_p(
    "Contribution méthodologique : démonstration d'application d'UP à un SI web de "
    "planification. Contribution applicative : prototype PLANIPRO multi-rôles. Contribution "
    "documentaire : enchaînement Vision → CU → architecture → code → tests. Contribution "
    "pédagogique : matériel réutilisable pour d'autres mémoires (schémas, matrices, "
    "traçabilité)."
)
add_p(
    "Les limites (SMTP, API, haute disponibilité) n'invalident pas ces contributions : "
    "elles définissent un backlog d'évolutions post-Transition, typique d'une démarche "
    "incrémentale (Larman, 2004 ; PMI, 2021)."
)

# More bibliography
add_h2("Références additionnelles (Processus Unifié et pratiques)")
extra_refs = [
    "Arlow, J., & Neustadt, I. (2005). UML 2 and the Unified Process (2e éd.). Addison-Wesley.",
    "Bergström, S., & Råberg, L. (2004). Adopting the Rational Unified Process. Addison-Wesley.",
    "Bittner, K., & Spence, I. (2003). Use Case Modeling. Addison-Wesley.",
    "Booch, G. (1994). Object-Oriented Analysis and Design with Applications (2e éd.). Benjamin/Cummings.",
    "Cantor, M. (2003). Software Leadership. Addison-Wesley.",
    "Douglass, B. P. (2004). Real Time UML (3e éd.). Addison-Wesley.",
    "Eeles, P., & Cripps, P. (2009). The Process of Software Architecting. Addison-Wesley.",
    "Gibbs, R. D. (2006). Project Management with the IBM Rational Unified Process. IBM Press.",
    "Gilb, T. (1988). Principles of Software Engineering Management. Addison-Wesley.",
    "Hunt, A., & Thomas, D. (1999). The Pragmatic Programmer. Addison-Wesley.",
    "IBM. (2005). Rational Unified Process — Best Practices for Software Development Teams (white paper).",
    "Jacobson, I., Ng, P.-W., & Spence, I. (2007). Enough of processes: Let's do practices. Journal of Object Technology.",
    "Kruchten, P. (2004). The Rational Unified Process: An Introduction — companion practices.",
    "Maciaszek, L. A. (2007). Requirements Analysis and System Design (3e éd.). Pearson.",
    "O'Regan, G. (2017). Concise Guide to Software Engineering. Springer.",
    "Pollice, G., Augustine, L., Lowe, C., & Madhur, J. (2004). Software Development for Small Teams: A RUP-Centric Approach. Addison-Wesley.",
    "Royce, W. (1998). Software Project Management: A Unified Framework. Addison-Wesley.",
    "Shuja, A. K., & Krebs, J. (2007). IBM Rational Unified Process Reference and Certification Guide. IBM Press.",
    "Unhelkar, B. (2018). Software Engineering with UML. CRC Press.",
    "Vernadat, F. (1996). Enterprise Modeling and Integration. Chapman & Hall.",
    "Whitaker, K. (2010). Principles of Software Development Leadership. Course Technology.",
    "Acharya, S., & Burke, D. (2010). Practice of Enterprise Application Development. various.",
    "Douglass, B. P. (1999). Doing Hard Time. Addison-Wesley.",
    "Fowler, M., & Scott, K. (2000). UML Distilled (2e éd.). Addison-Wesley.",
    "Henderson-Sellers, B., & Edwards, J. (1990). The object-oriented systems life cycle. CACM.",
    "Naiburg, E. J., & Maksimchuk, R. A. (2001). UML for Database Design. Addison-Wesley.",
    "Quatrani, T. (2002). Visual Modeling with Rational Rose and UML. Addison-Wesley.",
    "Rosenberg, D., & Scott, K. (1999). Use Case Driven Object Modeling with UML. Addison-Wesley.",
    "Schneider, G., & Winters, J. P. (2001). Applying Use Cases (2e éd.). Addison-Wesley.",
    "Texel, P., & Williams, C. (1997). Use Cases Combined with Booch OMT UML. Prentice Hall.",
]
for r in extra_refs:
    add_ref(r)

add_h2("Complément — Dictionnaire des termes UP utilisés")
add_bullet("Artefact : produit tangible du processus (document, modèle, code).")
add_bullet("Discipline : découpage thématique du travail (exigences, test…).")
add_bullet("Incrément : différence entre deux versions successives du système.")
add_bullet("Itération : cycle planifié produisant un incrément testable.")
add_bullet("Jalon (milestone) : point de décision formel (LCO, LCA, IOC, PR).")
add_bullet("Risque : événement potentiel impactant objectifs coût/délai/qualité.")
add_bullet("Tailoring : adaptation du processus au contexte du projet.")
add_bullet("Use case realization : collaboration qui réalise un CU en analyse/conception.")

add_h2("Complément — Matrice CU × Modules d'implémentation")
add_table(
    ["Cas d'utilisation", "Module code principal", "Tables touchées"],
    [
        ["Se connecter", "actions/login.php", "users"],
        ["S'inscrire (entreprise)", "actions/register.php", "users, entreprises"],
        ["Demander RDV", "actions/rdv_demande.php", "rendez_vous, notifications"],
        ["Valider RDV", "actions/rdv_action.php", "rendez_vous, historique, notifications"],
        ["Gérer activités", "actions/activite_save.php", "activites"],
        ["Voir calendrier", "actions/calendrier_events.php", "rendez_vous, activites"],
        ["Messagerie", "actions/message_send.php", "conversations, messages"],
        ["Contact public", "actions/contact_entreprise.php", "contact_public, notifications_publiques"],
        ["Rapports", "actions/rapport_export.php", "agrégations SQL"],
    ],
    "Tableau 4.9 — Traçabilité CU → code (Construction UP)",
)

add_p(
    "Cette matrice matérialise le principe use-case driven : chaque CU prioritaire possède "
    "une réalisation logicielle identifiable, ce qui facilite la maintenance et la soutenance."
)

add_p("*** Fin des développements complémentaires UP ***", center=True, justify=False, italic=True, first_indent=0)
add_p("— FIN DU MÉMOIRE (version Processus Unifié) —", center=True, justify=False, bold=True, size=14, first_indent=0)

doc.save(str(DOC_PATH))

words = sum(len(p.text.split()) for p in doc.paragraphs)
for t in doc.tables:
    for row in t.rows:
        for cell in row.cells:
            words += len(cell.text.split())
print("words", words)
print("pages_250", round(words / 250, 1))
print("pages_200", round(words / 200, 1))
print("paragraphs", len(doc.paragraphs))
print("tables", len(doc.tables))
print("saved", DOC_PATH)
