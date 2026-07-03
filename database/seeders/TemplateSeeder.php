<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // =========================================================
        // 1. Three Act Structure (System Template)
        // =========================================================

        $threeAct = Template::create([
            'user_id' => null, 
            'name' => 'Three-Act Structure',
            'description' => <<<'MARKDOWN'
            The Three-Act Structure is the most classic and widely used narrative model in screenwriting, novel writing, and even oral storytelling. It divides a story into three distinct parts: the Setup, the Confrontation, and the Resolution.

            Think of it like a bridge: Act 1 is the solid foundation on one side, Act 2 is the long, winding path over the water, and Act 3 is the landing on the other side—only this time, the traveler is forever changed.
            MARKDOWN,
            'image_preview' => 'structures.three-act',
            'is_custom' => false,
        ]);

        $threeAct->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Act I: The Setup',
                'goal' => <<<'MARKDOWN'
                Duration: Approximately 25% of the story.

                Formally, this is the Exposition phase. This is where the audience is introduced to the story's world, the main character (the protagonist), and the rules of the universe.

                * **The Status Quo (Ordinary Life):** Simply put, this is the "Once upon a time..." moment. We see the character's daily life before everything gets flipped upside down.
                * **Inciting Incident (The Catalyst):** This is the "But one day..." moment. An event occurs that disrupts the protagonist's comfort zone and demands action.
                * **Plot Point 1:** This is the exit door from Act 1. The character makes a conscious decision to accept the challenge. Once they pass this point, there is no going back to their old life.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Act II: The Confrontation',
                'goal' => <<<'MARKDOWN'
                Duration: Approximately 50% of the story (The longest part).

                Formally known as the Rising Action. The protagonist tries to solve the problem, but obstacles and complications keep stacking up.
                * **Fun and Games (Tests & Obstacles):** This is where the character learns to "swim" in the new world. They meet allies, encounter enemies, and face minor hurdles. Easy explanation: This is the "meat" of the story or the core adventure.
                * **The Midpoint:** A major event in the dead center of the story where the stakes suddenly skyrocket. The character shifts from being "reactive" (just surviving) to "proactive" (actually taking the fight to the problem).
                * **All is Lost & The Dark Night of the Soul:** Toward the end of Act 2, the character usually suffers a crushing defeat. They feel like a failure and want to give up.
                * **Plot Point 2:** A new piece of information or a surge of motivation that forces the character to pick themselves up for the final fight.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Act III: The Resolution',
                'goal' => <<<'MARKDOWN'
                Duration: Approximately 25% of the story.

                This is the Conclusion. All the tension built since Act 1 must be paid off here.
                * **The Climax:** The "Final Boss Battle." This is the peak of the conflict. The protagonist must use everything they’ve learned to defeat the antagonist or resolve their internal struggle.
                * **The Resolution/Denouement:** After the storm passes, we see how the dust settles. The tension is released, and the subplots are tied up.
                * **New Status Quo:** The new life established after the conflict. The world has changed, and the protagonist has undergone a "Character Arc" (transformation).
                MARKDOWN
            ]
        ]);

        // =========================================================
        // 2. Dan's Harmon Story Circle Structure (System Template)
        // =========================================================

        $storyCircle = Template::create([
            'user_id' => null,
            'name' => 'Dan Harmon\'s Story Circle',
            'description' => <<<'MARKDOWN'
            Dan Harmon’s Story Circle (also known as the Plot Embryo) is a streamlined, eight-step iteration of the "Hero’s Journey." Designed by the creator of Community and Rick and Morty, it ensures that a story remains focused on character evolution through a rhythmic cycle of order and chaos.

            Imagine it like a revolving door: You start in the light of the familiar, push through into the darkness of the unknown to get what you need, and swing back out into the light—but you are no longer the same person who walked in.
            MARKDOWN,
            'image_preview' => 'structures.dan-harmons-story-circle',
            'is_custom' => false,
        ]);

        $storyCircle->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Zone of Comfort',
                'goal' => <<<'MARKDOWN'
                This is the introduction to the protagonist in their "Ordinary World." It establishes the status quo—how they live, what they do, and who they think they are. While they are safe and comfortable, there is usually a subtle hint of stagnation or a latent flaw. For a writer, this step is about making the audience care about the character before their life is disrupted.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Need or Desire',
                'goal' => <<<'MARKDOWN'
                The character realizes something is missing. This could be an external lack (money, safety, a missing person) or an internal void (validation, purpose, love). This "need" creates a specific goal. It is the engine of the story; without a clear desire, the character has no reason to move. This is the moment the character realizes that their current "Zone of Comfort" is no longer enough.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Crossing a Threshold',
                'goal' => <<<'MARKDOWN'
                The protagonist makes a conscious choice to leave their familiar surroundings and step into the "Unknown World." This is the point of no return. Whether they are literally traveling to a new land or metaphorically entering a new situation (like a first day at a high-stakes job), they have crossed the line from Order into Chaos. The story officially shifts gears here.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Rode of Trials',
                'goal' => <<<'MARKDOWN'
                The protagonist struggles but eventually learns the rules of this new world. They face trials and face failures.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Get What They Wanted',
                'goal' => <<<'MARKDOWN'
                The protagonist achieves their initial goal or finds the object of their desire, but usually at a great cost or realizing it's not what they truly needed.
                MARKDOWN
            ],
            [
                'order_index' => 6,
                'title' => 'Pay a Heavy Price',
                'goal' => <<<'MARKDOWN'
                The protagonist faces the consequences of getting what they wanted. This is often the darkest moment of the story.
                MARKDOWN
            ],
            [
                'order_index' => 7,
                'title' => 'Return to Comfort',
                'goal' => <<<'MARKDOWN'
                The protagonist heads back to their ordinary world, bringing whatever they've learned or gained with them.
                MARKDOWN
            ],
            [
                'order_index' => 8,
                'title' => 'Having Changed',
                'goal' => <<<'MARKDOWN'
                The protagonist is back where they started, but they are fundamentally changed. They have grown from the experience.
                MARKDOWN
            ],
        ]);

        // =========================================================
        // 3. Freytag's Comandment Structure (System Template)
        // =========================================================

        
        $freytags = Template::create([
            'user_id' => null, 
            'name' => 'Freytag\'s Pyramid',
            'description' => <<<'MARKDOWN'
            Freytag’s Pyramid is one of the oldest and most fundamental frameworks in literary analysis, developed by 19th-century German playwright Gustav Freytag. Originally designed to map the structure of five-act Greek and Shakespearean tragedies, it remains a vital tool for writers who want to understand the rise and fall of dramatic tension.

            Think of this structure as a mountain climb. You spend the first half of the journey ascending toward a singular, life-changing peak, and the second half dealing with the momentum of the descent—where every choice made on the way up determines whether you land safely or crash at the bottom.
            MARKDOWN,
            'image_preview' => 'structures.freytags',
            'is_custom' => false,
        ]);

        $freytags->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Inciting Incident',
                'goal' => <<<'MARKDOWN'
                This is the event that upsets the initial balance of the protagonist's life. It can be a Causal event or a Coincidental event.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Turning Point Progressive Complication',
                'goal' => <<<'MARKDOWN'
                An action or revelation that changes the direction of the story and forces the protagonist to react. It makes the protagonist's goal harder to achieve.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Crisis',
                'goal' => <<<'MARKDOWN'
                The moment the protagonist must make a difficult choice between two bad things or two irreconcilable goods. This is the core dilemma.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Climax',
                'goal' => <<<'MARKDOWN'
                The active execution of the choice made in the Crisis. It is the peak of tension where the protagonist takes action.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Resolution',
                'goal' => <<<'MARKDOWN'
                The result of the Climax. It establishes a new world order and answers the dramatic question posed by the Inciting Incident.
                MARKDOWN
            ],
        ]);

        // =========================================================
        // 4. Five Commandment Structure (System Template)
        // =========================================================

        $fiveCommandments = Template::create([
            'user_id' => null, 
            'name' => 'The Five Commandments',
            'description' => <<<'MARKDOWN'
            The Five Commandments of Storytelling is a fundamental framework developed by Shawn Coyne as part of the Story Grid methodology. These five elements are the "DNA" of a story; they must exist in every scene, every sequence, every act, and the global story itself to ensure the narrative is functional and engaging.

            Think of these commandments as the mechanical gears of a clock. If one gear is missing, the story stops ticking. They ensure that a character is constantly forced to make meaningful choices that reveal their true nature.
            MARKDOWN,
            'image_preview' => 'structures.five-commandments',
            'is_custom' => false,
        ]);

        $fiveCommandments->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Inciting Incident',
                'goal' => <<<'MARKDOWN'
                This is the event that upsets the initial balance of the protagonist's life. It can be a Causal event (someone makes a decision that affects the hero) or a Coincidental event (a random act of fate). This incident knocks the character off-balance and gives them a "Goal" to return things to normal or to reach a new state of stability. For the writer, this is the "hook" that demands the character take action.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Turning Point Progressive Complication',
                'goal' => <<<'MARKDOWN'
                As the character pursues their goal, they encounter obstacles. The most critical obstacle is the Turning Point. This is a complication that makes the character's initial plan impossible to continue. It can be an Action (something happens to them) or a Revelation (they learn something new). It effectively "turns" the story in a new direction, leaving the character at a dead end where their old way of thinking no longer works.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Crisis',
                'goal' => <<<'MARKDOWN'
                This is the most important commandment. Once the Turning Point occurs, the character is forced to make a choice. A true Crisis is not a choice between "Good" and "Bad" (which is easy); it is a choice between two Irreconcilable Goods (you can only have one) or the Best Bad Choice (two terrible options, and you must pick the least harmful). This is the "Dark Night of the Soul" in miniature, where the character’s internal values are put to the ultimate test.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Climax',
                'goal' => <<<'MARKDOWN'
                This is the character's answer to the Crisis. It is the physical or verbal action they take based on their decision. The Climax is where the character's "mask" is removed—we see who they truly are by what they do when the pressure is highest. A writer must ensure the Climax is a direct result of the Crisis; it is the moment the character finally commits to a path.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Resolution',
                'goal' => <<<'MARKDOWN'
                This is the aftermath of the Climax. It shows the audience exactly what has changed as a result of the character's choice. The world has shifted to a new state of equilibrium. It provides the "takeaway" for the scene or story, showing the change in the character's circumstances or their internal growth. Without a resolution, the audience feels the story was cut off before they could process the meaning of the Climax.
                MARKDOWN
            ],
        ]);

        // =========================================================
        // 5. Save The Cat Structure (System Template)
        // =========================================================

        $saveTheCat = Template::create([
            'user_id' => null, 
            'name' => 'Save The Cat!',
            'description' => <<<'MARKDOWN'
            This is the aftermath of the Climax. It shows the audience exactly what has changed as a result of the character's choice. The world has shifted to a new state of equilibrium. It provides the "takeaway" for the scene or story, showing the change in the character's circumstances or their internal growth. Without a resolution, the audience feels the story was cut off before they could process the meaning of the Climax.
            MARKDOWN,
            'image_preview' => 'structures.save-the-cat',
            'is_custom' => false,
        ]);

        $saveTheCat->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Inciting Incident',
                'goal' => <<<'MARKDOWN'
                This is the event that upsets the initial balance of the protagonist's life. It can be a Causal event or a Coincidental event.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Turning Point Progressive Complication',
                'goal' => <<<'MARKDOWN'
                An action or revelation that changes the direction of the story and forces the protagonist to react. It makes the protagonist's goal harder to achieve.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Crisis',
                'goal' => <<<'MARKDOWN'
                The moment the protagonist must make a difficult choice between two bad things or two irreconcilable goods. This is the core dilemma.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Climax',
                'goal' => <<<'MARKDOWN'
                The active execution of the choice made in the Crisis. It is the peak of tension where the protagonist takes action.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Resolution',
                'goal' => <<<'MARKDOWN'
                The result of the Climax. It establishes a new world order and answers the dramatic question posed by the Inciting Incident.
                MARKDOWN
            ],
        ]);

        // =========================================================
        // 6. 7 Point Story Structure (System Template)
        // =========================================================

        $sevenPointStory = Template::create([
            'user_id' => null, 
            'name' => 'Seven Point Story',
            'description' => <<<'MARKDOWN'
            The 7-Point Story Structure is a highly versatile framework popularized by author Dan Wells. It is often described as a "backward" planning method because it encourages writers to define the ending first and then build the starting point as its perfect opposite. This ensures that every beat in the story is laser-focused on moving the protagonist toward their final transformation.
            Think of this structure as a series of waypoints on a map. It doesn't dictate every single scene, but it provides the critical structural "anchors" that keep the narrative on track, ensuring that the tension rises and the character evolves in a way that feels earned and inevitable.
            MARKDOWN,
            'image_preview' => 'structures.seven-point',
            'is_custom' => false,
        ]);

        $sevenPointStory->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Hook',
                'goal' => <<<'MARKDOWN'
                This is the event that upsets the initial balance of the protagonist's life. It can be a Causal event or a Coincidental event.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Turning Point Progressive Complication',
                'goal' => <<<'MARKDOWN'
                An action or revelation that changes the direction of the story and forces the protagonist to react. It makes the protagonist's goal harder to achieve.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Crisis',
                'goal' => <<<'MARKDOWN'
                The moment the protagonist must make a difficult choice between two bad things or two irreconcilable goods. This is the core dilemma.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Climax',
                'goal' => <<<'MARKDOWN'
                The active execution of the choice made in the Crisis. It is the peak of tension where the protagonist takes action.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Resolution',
                'goal' => <<<'MARKDOWN'
                The result of the Climax. It establishes a new world order and answers the dramatic question posed by the Inciting Incident.
                MARKDOWN
            ],
        ]);

    
        // =========================================================
        // 7. 27 Chapter Method Structure (System Template)
        // =========================================================

        $ChapterMethod27 = Template::create([
            'user_id' => null, 
            'name' => '27 Chapter Method',
            'description' => <<<'MARKDOWN'
            The 27 Chapter Method is a hyper-structured evolution of the classic Three-Act Structure, breaking the narrative into nine blocks of three chapters each. Popularized by writers like Kat O'Keeffe, this method provides a literal roadmap for a first draft, ensuring that every 10% of the book has a specific purpose and thematic movement.

            Think of it as a meticulous blueprint. Where other structures give you the general shape of the house, this method tells you exactly where every brick and beam should go, preventing the dreaded "middle slump" and ensuring a fast-paced, high-stakes journey from start to finish.
            MARKDOWN,
            'image_preview' => 'structures.chapter-method-27',
            'is_custom' => false,
        ]);

        $ChapterMethod27->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Hook',
                'goal' => <<<'MARKDOWN'
                This is the event that upsets the initial balance of the protagonist's life. It can be a Causal event or a Coincidental event.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Turning Point Progressive Complication',
                'goal' => <<<'MARKDOWN'
                An action or revelation that changes the direction of the story and forces the protagonist to react. It makes the protagonist's goal harder to achieve.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Crisis',
                'goal' => <<<'MARKDOWN'
                The moment the protagonist must make a difficult choice between two bad things or two irreconcilable goods. This is the core dilemma.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Climax',
                'goal' => <<<'MARKDOWN'
                The active execution of the choice made in the Crisis. It is the peak of tension where the protagonist takes action.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Resolution',
                'goal' => <<<'MARKDOWN'
                The result of the Climax. It establishes a new world order and answers the dramatic question posed by the Inciting Incident.
                MARKDOWN
            ],
        ]);
    }
}