Title
WP-SurveyKit: An Open, Self-Hosted WordPress Plugin for Psychologically Oriented Online Surveys With Ephemeral Login and Post-Hoc Anonymization

Abstract
Background: Online survey platforms such as Qualtrics, REDCap, and SurveyMonkey play a central role in psychological research, clinical monitoring, and higher education. However, they are often tied to institutional licenses, external data hosting, or technical infrastructure that is not universally available. In many low-resource or privacy-sensitive settings, researchers and practitioners rely on WordPress as their primary web infrastructure but lack a dedicated, self-hosted survey solution tailored to psychological and clinical applications.
Objective: This article presents WP-SurveyKit, a WordPress plugin designed to enable self-hosted online surveys with features specifically aligned with psychological and clinical research workflows. The objectives are: (1) to provide an infrastructure-level tool that can be installed on existing WordPress sites; (2) to support longitudinal data collection using ephemeral, survey-specific login sessions; (3) to implement an explicit, irreversible post-hoc anonymization procedure at survey closure; and (4) to adopt a transparent, demand-driven development model governed by an open roadmap.
Methods: We describe the conceptual design of WP-SurveyKit, its integration with the WordPress ecosystem, and its relational data model. The plugin separates three layers of information: authentication data, participant linkage, and item-level responses. During active data collection, participants access surveys via survey-bound accounts or unique tokens that allow longitudinal tracking without creating permanent WordPress user accounts. Upon survey closure, an anonymization routine irreversibly destroys the link between identifiable information and item-level data. We also outline the project’s governance model, based on incremental evolution “on demand”, with a public roadmap that documents proposals, prioritization criteria, and accepted contributions.
Results: WP-SurveyKit provides a minimal yet extensible backbone for online survey research within WordPress. It allows researchers to (a) deploy single-wave or multi-wave questionnaires, (b) manage ephemeral login sessions for repeated measures, (c) export pseudonymized or fully anonymized datasets for statistical analysis, and (d) document and negotiate new features through an open, community-visible development process. We illustrate typical use cases in university teaching, clinical pilot monitoring, and low-resource research settings.
Conclusions: WP-SurveyKit addresses a gap between general-purpose WordPress forms and specialized institutional survey platforms by offering a self-hosted, privacy-oriented tool tailored to psychological and clinical research needs. Its design emphasizes local data control, simplicity, and governance transparency. Future development will extend support for randomization, longitudinal email workflows, and programmatic integration with statistical environments such as R and Python.
Keywords: online surveys; WordPress; psychometrics; clinical research; longitudinal data; anonymization; open-source software; measurement-based care

1. Introduction
Online surveys are central to contemporary psychological research, clinical assessment, and educational evaluation. They are used to administer self-report questionnaires, measure symptoms, track treatment progress, monitor student experiences, and collect feedback across a wide range of applied contexts. Over the past decades, commercial and institutional platforms such as Qualtrics, REDCap, and SurveyMonkey have become standard tools for implementing these procedures.
Despite their strengths, these platforms present several limitations. They often rely on external data hosting, are bound to institutional licenses, or require specialized infrastructure and user management. In many low- and middle-resource settings, researchers and practitioners do not have access to full institutional licenses or cannot rely on external servers for storing sensitive psychological or clinical information. At the same time, many universities, clinics, and non-profit organizations already run WordPress as their web infrastructure, due to its low cost, flexibility, and widespread adoption.
WordPress includes numerous form plugins, but these tools are usually designed for contact forms, lead generation, or simple feedback; they rarely support longitudinal research designs, explicit post-hoc anonymization, or governance models centered on scientific transparency. To our knowledge, there is no dedicated survey plugin for WordPress that explicitly addresses the methodological, ethical, and data-governance requirements typical of psychological and clinical research.
To fill this gap, we developed WP-SurveyKit, a WordPress plugin that provides a minimal yet extensible infrastructure for online surveys tailored to psychological and clinical use cases. This article presents the conceptual design, technical architecture, and governance model of WP-SurveyKit, with particular emphasis on three core features:
Ephemeral, survey-specific login sessions for longitudinal data collection without creating permanent WordPress user accounts.
An explicit anonymization procedure that can be executed at survey closure, irreversibly severing the link between identifiable information and item-level responses.
A demand-driven development model with a public roadmap that documents feature requests, prioritization, and community contributions.
We argue that this combination of features makes WP-SurveyKit a suitable infrastructure component for self-hosted survey research in psychology, clinical practice, and higher education, particularly in contexts where external platforms are not available or not acceptable for sensitive data.

2. Design Goals
The design of WP-SurveyKit was guided by four overarching goals.
2.1 Local data control
First, the plugin aims to ensure that all data are stored on the organization’s own server, under its existing WordPress and database infrastructure. This is crucial for institutions that must comply with strict data protection rules, or that prefer to keep clinical or educational data within their own governance perimeter. Self-hosting also facilitates integration with existing backup procedures, authentication systems, and institutional policies.
2.2 Minimal, infrastructure-level complexity
Second, WP-SurveyKit is intentionally minimal. Rather than replicating the breadth of features offered by large survey platforms, it focuses on providing a backbone: a reliable way to create surveys, collect responses, manage sessions, and export data. More complex behaviors—such as advanced branching, dashboarding, or statistical analysis—are considered out of scope for the core plugin and are candidates for future extensions or external tools.
2.3 Privacy by design
Third, the plugin applies a privacy-by-design approach. During data collection, it separates authentication information, participant identifiers, and item-level responses into distinct layers. When a study is complete, a “close and anonymize” procedure can be triggered, destroying the linkage between identities and item-level data and leaving only irreversibly anonymized records for analysis.
2.4 Demand-driven evolution and open governance
Finally, WP-SurveyKit is developed under a demand-driven approach. New features are implemented only when they correspond to clearly documented needs in research, clinical practice, or teaching. The project maintains a public roadmap that documents proposals, decisions, and development status, and invites community contributions that align with its guiding principles: necessity over novelty, simplicity over accumulation, privacy over convenience, and local control over external dependency.

3. System Overview
3.1 Integration with WordPress
WP-SurveyKit is implemented as a standard WordPress plugin. Once installed and activated via the WordPress administrative interface, it registers a custom post type, for example survey, that represents individual surveys within the system.
Survey authors can create and configure surveys through the WordPress dashboard, using an administrative interface that exposes:
basic survey metadata (title, description, instructions);
item management (question text, response format);
access and session settings (authentication method, longitudinal options);
data export and anonymization controls.
Surveys can be embedded into WordPress pages or posts via shortcodes or blocks, enabling integration into existing websites or dedicated research portals.
3.2 Survey item types
The plugin supports a core set of item types commonly used in psychological research:
Likert-type items (e.g., 5- or 7-point response options);
visual analog scales (VAS), with optional single or multiple labels;
single-choice and multiple-choice categorical responses;
open-ended text fields.
Additional item formats can be implemented via extensible interfaces, allowing future modules to introduce specialized response types (e.g., sliders with multi-label anchors, or composite indices).

4. Data Model
The data model is centered on three conceptual layers: authentication, participant linkage, and item-level responses. This separation is key to implementing ephemeral login and post-hoc anonymization.
4.1 Authentication layer
Authentication is handled by a dedicated table, conceptually referred to as wp_survey_auth. Each record corresponds to a survey-specific access method for an individual participant. Fields typically include:
a primary key (auth_id);
the associated survey identifier (survey_id);
a username, email, or login token;
a password hash or secret (when required);
metadata such as creation time and last access.
Importantly, these authentication records are decoupled from WordPress’s native user accounts. Participants do not need to register as WordPress users; instead, they receive survey-specific credentials (or unique links) that allow them to access the corresponding survey during active data collection.
4.2 Participant linkage layer
The participant linkage layer is implemented as a separate table (e.g., wp_survey_participants), which connects authentication records to anonymous participant identifiers used in the response table. Fields include:
a primary key (participant_id);
the associated survey identifier (survey_id);
a foreign key to the authentication table (auth_id);
optional non-identifying grouping variables (e.g., condition, cohort);
optional identifying fields (e.g., email, name) if explicitly required for the study.
This table functions as a pseudonymization layer: item-level responses are linked to participant_id, not directly to authentication or personal identifiers.
4.3 Response layer
Item-level data are stored in a response table (e.g., wp_survey_responses), which is the main source for subsequent statistical analysis. Typical fields include:
a primary key (response_id);
the survey identifier (survey_id);
the participant identifier (participant_id);
the item identifier (item_id);
the wave or timepoint (for longitudinal designs);
a timestamp;
the recorded response value, stored in a format appropriate to the item type.
Longitudinal designs are supported by including a wave index or timestamp and allowing multiple responses per participant and per survey.

5. Ephemeral Login and Longitudinal Sessions
5.1 Ephemeral accounts
During active data collection, participants can be invited to the survey using two primary modes:
Credential-based login, where each participant is assigned a survey-specific username and password (or similar credential).
Token-based access, where each participant receives a unique URL containing a secure token that authenticates them automatically.
In both cases, the authentication record is bound to a single survey and is not reused across other parts of the WordPress site. This creates an ephemeral account: it exists solely for the duration of the data collection and for a single study.
5.2 Longitudinal tracking
For longitudinal designs, participants may complete the same survey multiple times (e.g., weekly symptom monitoring, pre-post assessments, repeated classroom evaluations). The plugin links all responses from the same participant and survey through participant_id, while distinguishing different timepoints via a wave or timestamp field.
Longitudinal participation can be supported through:
scheduled email reminders with individualized links;
manual invitations;
or integration with external scheduling tools.
Because authentication is survey-specific and ephemeral, longitudinal tracking is limited to the scope of the individual study and does not imply the creation of a persistent user profile across the website.

6. Post-Hoc Anonymization Procedure
6.1 Conceptual rationale
From a data protection perspective, the system moves through two phases:
Pseudonymized phase: While the survey is active, responses are linked to participants through participant_id, which in turn is linked (via auth_id) to authentication records and, optionally, to personal identifiers. At this stage, participants may exercise rights such as withdrawal or data deletion at the individual level.
Anonymized phase: Once the survey is closed and the anonymization procedure has been executed, the link between item-level data and any identifiable or re-identifiable information is irreversibly destroyed. From this point onwards, it is no longer possible to associate a given response profile with a specific individual.
This design supports ethical and legal requirements that distinguish between pseudonymized and truly anonymized data sets.
6.2 Implementation
When the researcher or administrator decides that data collection is complete, they may trigger a “close and anonymize” action in the survey’s administrative interface. Conceptually, this action performs the following operations for the target survey:
Remove or nullify identifying fields from the participant linkage table (wp_survey_participants), such as names, email addresses, or other direct identifiers.
Destroy the authentication layer for that survey by deleting the corresponding records in wp_survey_auth or rendering them unusable (e.g., by invalidating tokens and clearing passwords).
Optionally remap participant identifiers (e.g., to sequential anon_id values) and update the response table to use these anonymized identifiers instead of the original participant_id.
After this procedure, there is no remaining internal mechanism in the system to connect the anonymized response data to identifiable persons. Researchers who wish to retain the ability to delete specific individuals’ data after closure must explicitly avoid executing the anonymization procedure and instead maintain surveys in the pseudonymized state, which has different ethical and regulatory implications.
6.3 Informed consent and participant rights
The dual-phase design implies a clear requirement for transparent communication with participants. Consent forms and study protocols should specify:
that during data collection, responses are linked to individuals in a pseudonymized way, allowing individual withdrawal or deletion upon request; and
that after a certain point (survey closure and anonymization), it becomes technically impossible to remove individual records, because they can no longer be associated with specific persons.
WP-SurveyKit does not enforce any specific consent model but provides the technical affordances to support ethically sound procedures.

7. Governance Model and Demand-Driven Development
7.1 Development philosophy
WP-SurveyKit is not intended as a feature-complete competitor to comprehensive platforms such as Qualtrics or REDCap. Instead, it is designed as a lean infrastructure tool that evolves according to documented needs. To prevent uncontrolled growth in complexity and maintain a low barrier to deployment and maintenance, the project follows a demand-driven development philosophy:
New features are implemented only when they correspond to real, documented use cases in research, teaching, or practice.
Proposals are evaluated against the project’s guiding principles:
Necessity over novelty (does this solve a concrete problem?),
Simplicity over accumulation (does it preserve a clean architecture?),
Privacy over convenience (does it respect privacy by design?),
Local control over external dependency (does it preserve self-hosting and independence?).
7.2 Public roadmap
To operationalize this philosophy, the project maintains a public roadmap, for example as a dedicated page in the repository or documentation site. The roadmap lists:
all active proposals and feature requests;
their current status (under review, accepted, in development, completed, or rejected);
rationale for prioritization decisions;
dependencies and estimated complexity;
explicit calls for contributions when external help is welcomed.
This transparency allows users and potential contributors to understand how decisions are made, which features are prioritized, and where they might meaningfully engage.
7.3 Community contributions
External contributions are welcome but must align with the project’s principles. A dedicated contribution guide (e.g., CONTRIBUTING.md) specifies the expectations for:
proposing new features (including a minimal case description and justification);
submitting code (style, testing, documentation requirements);
discussing design decisions (e.g., via issue templates or discussion boards).
Features that substantially increase complexity, introduce external dependencies, or conflict with privacy-by-design assumptions are likely to be rejected or deferred, even if technically feasible. This governance model aims to balance openness to collaboration with stability and conceptual coherence.

8. Illustrative Use Cases
8.1 University teaching
In a university psychology course, instructors wish to collect weekly self-report measures on students’ stress levels, engagement, and perceived workload. The institution already runs a WordPress-based learning environment but does not possess a Qualtrics or REDCap license.
Using WP-SurveyKit, instructors can:
create a survey with Likert scales and open-ended questions;
generate survey-specific login credentials for enrolled students;
collect repeated measures over the semester; and
export an anonymized dataset at the end of the course for aggregated analysis and feedback.
Because data reside on the university server and can be anonymized post-hoc, the system respects institutional data policies and facilitates ethical classroom research.
8.2 Clinical pilot monitoring
A clinical team implementing a pilot program in psychotherapy aims to monitor client-reported symptoms over the course of treatment. They require local storage due to regulatory constraints and wish to avoid dependency on external platforms.
WP-SurveyKit allows the team to:
configure a symptom questionnaire as an online survey;
provide each client with a survey-specific access link that they can use before or after each session;
store responses in the clinic’s server; and
anonymize data after the pilot concludes, while preserving the structure necessary for longitudinal analysis (e.g., patient-level trajectories).
Such a configuration can form the basis for low-cost, self-hosted monitoring, even if it does not replace specialized clinical information systems.
8.3 Low-resource research environments
In low-resource universities or community organizations, researchers may have minimal server infrastructure but can deploy WordPress applications on affordable hosting. WP-SurveyKit enables them to implement surveys using the tools they already manage, without the need for additional external subscriptions or platforms. This can be particularly valuable in student theses, small grants, or exploratory studies where larger systems are not feasible.

9. Limitations
WP-SurveyKit has several important limitations.
First, it is not intended as a full replacement for large survey platforms. As of its initial versions, it does not provide advanced branching logic, complex randomization schemes, or built-in statistical reports. Such features may be implemented incrementally but remain outside the core design.
Second, the plugin does not, by itself, satisfy any specific regulatory framework (such as HIPAA or GDPR) in a legal sense. It provides technical affordances (local storage, pseudonymization, anonymization), but compliance depends on the broader institutional context, including server configuration, access control, encryption, and governance policies.
Third, the plugin’s security model relies on WordPress’ overarching security and the server’s configuration. Misconfigured sites may expose vulnerabilities that affect survey data. As such, WP-SurveyKit should be deployed only on properly maintained and monitored WordPress installations.
Fourth, the epistemic limitations of online self-report instruments apply equally here: WP-SurveyKit does not address issues such as response bias, measurement error, or psychometric validity. It is an infrastructural tool; the scientific quality of measurements remains the responsibility of researchers and practitioners.

10. Future Work
Future development is planned along several directions, subject to the demand-driven governance model:
Randomization and experimental features: Support for random assignment of participants to conditions, random ordering of items, and simple A/B testing designs.
Longitudinal email workflows: More flexible and configurable email scheduling for repeated measures, including fixed intervals, event-based triggers, and customizable templates.
API integration: A RESTful API to allow direct integration with statistical software such as R and Python, enabling automated extraction, preprocessing, and analysis pipelines.
Enhanced item formats: Support for more complex response formats, such as multi-label visual analog scales, grid questions, or composite scales with internal scoring rules.
Optional dashboards: Lightweight, non-clinical dashboards for lecturers or researchers to visualize aggregated data, without attempting to provide full clinical decision support.
All such developments will be evaluated in terms of necessity, complexity, and alignment with privacy-by-design principles before being added to the public roadmap.

11. Conclusion
WP-SurveyKit offers a self-hosted, WordPress-based infrastructure for online surveys tailored to the practical and ethical needs of psychological, clinical, and educational research. By combining survey-specific ephemeral login, a layered data model, and an explicit post-hoc anonymization procedure, it enables longitudinal data collection with robust local control over sensitive information.
The project’s demand-driven governance model and open roadmap are intended to ensure that evolution remains aligned with real-world use cases, avoids unnecessary complexity, and fosters community participation under clear guiding principles. WP-SurveyKit is not a universal replacement for established survey platforms; rather, it occupies a niche where local control, affordability, and integration with existing WordPress ecosystems are paramount.
As digital methods become increasingly central to psychological and clinical science, tools like WP-SurveyKit can contribute to a more diverse and context-sensitive ecosystem of research infrastructure, particularly in settings where access to large external platforms is limited or undesirable.

12. Acknowledgments
[Insert any funding, institutional support, or contributors you wish to acknowledge.]

13. Author Contributions
[Define roles using, for example, the CRediT taxonomy: conceptualization, software, methodology, writing – original draft, writing – review & editing, etc.]

14. Data and Code Availability
The source code of WP-SurveyKit is available at:
[Insert repository URL]
Releases will be archived with a persistent identifier (e.g., DOI via Zenodo), and users are encouraged to cite the software using the recommended citation provided in the repository.

