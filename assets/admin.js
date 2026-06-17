document.addEventListener(
    'DOMContentLoaded',
    function () {

        const button =
            document.getElementById(
                'schema-orchestrator-preview'
            );

        if (!button) {
            return;
        }

        button.addEventListener(
            'click',
            async function () {

                const output =
                    document.getElementById(
                        'schema-orchestrator-preview-output'
                    );

                const postId =
                    document.getElementById(
                        'post_ID'
                    ).value;

                output.value = 'Loading...';

                const body =
                    new URLSearchParams();

                body.append(
                    'action',
                    'schema_orchestrator_preview'
                );

                body.append(
                    'post_id',
                    postId
                );

                body.append(
                    'nonce',
                    SchemaOrchestrator.nonce
                );

                const response =
                    await fetch(
                        SchemaOrchestrator.ajax_url,
                        {
                            method: 'POST',
                            body
                        }
                    );

                const json =
                    await response.json();

                output.value =
                    JSON.stringify(
                        json.data,
                        null,
                        2
                    );
            }
        );
    }
);